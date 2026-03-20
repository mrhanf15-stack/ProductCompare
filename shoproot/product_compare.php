<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v1.9.1 - Comparison Page (Original Listing Design)
   File: product_compare.php (shoproot)
   
   Uses the shop's own product class buildDataArray() to generate
   the SAME Smarty variables as product_listing_include.html.
   
   Flow follows the same pattern as seedfinder.php:
   1. require application_top.php
   2. $smarty = new Smarty (required!)
   3. Page logic (load products, assign variables)
   3. $main_content = $smarty->fetch(template)
   4. require header.php (loads ALL box modules: cart, wishlist, search, etc.)
      -> header.php calls autoinclude header_head/ which sets $meta_title + $meta_description
   5. $smarty->display(index.html)
   6. require application_bottom.php
   
   @author    Mr. Hanf / Manus AI
   @version   1.9.1
   @date      2026-03-20
   
   v1.9.1 Changes:
   - BUGFIX: Clear-Action löscht jetzt auch das Cookie 'pc_compare_ids'
   - BUGFIX: Remove-Action synchronisiert Cookie serverseitig
   
   v1.7.9 Changes:
   - Sitemap-Option entfernt (wird extern verwaltet)
   - Auto-Update raeumt altes Sitemap-Feld automatisch auf
   
   v1.7.8 Changes:
   - Auto-Update: fehlende Konfigurationsfelder werden automatisch angelegt
   
   v1.7.7 Changes:
   - Meta-Titel and Meta-Description configurable via Admin backend
   - Autoinclude header_head sets meta tags for SEO
   - Sitemap integration via autoinclude
   - Canonical URL for product compare page
   
   v1.7.6 Changes:
   - Image fix: position absolute + object-fit cover to FORCE equal height for ALL images
   - Listing button code snippet provided for product_listing.html integration
   
   v1.7.5 Changes:
   - contentAnywhere modifier support (use {ID|contentAnywhere|inserttags} in template)
   - Product images: object-fit cover with !important to force equal height even for small images
   - Removed img-fluid class that prevented small images from scaling up
   - Back button: "Weiter einkaufen" with history.back()
   -----------------------------------------------------------------------------------------*/

require('includes/application_top.php');

// Create Smarty instance (required - application_top does NOT create it)
$smarty = new Smarty;
$smarty->assign('language', $_SESSION['language']);
$smarty->assign('tpl_path', DIR_WS_BASE.'templates/'.CURRENT_TEMPLATE.'/');

// Load box modules (cart, wishlist, search, languages, categories, menu etc.)
// Same as seedfinder.php line 505
require(DIR_FS_CATALOG.'templates/'.CURRENT_TEMPLATE.'/source/boxes.php');

// Load language file
$pc_lang_file = DIR_WS_LANGUAGES . $_SESSION['language'] . '/extra/product_compare.php';
if (file_exists($pc_lang_file)) {
    require_once($pc_lang_file);
}

// Init session
if (!isset($_SESSION['product_compare'])) {
    $_SESSION['product_compare'] = array();
}

// Action: remove product
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['products_id'])) {
    $remove_id = (int)$_GET['products_id'];
    $key = array_search($remove_id, $_SESSION['product_compare']);
    if ($key !== false) {
        unset($_SESSION['product_compare'][$key]);
        $_SESSION['product_compare'] = array_values($_SESSION['product_compare']);
    }
    // v1.9.1: Cookie synchronisieren nach Remove
    $pc_cookie_value = implode(',', array_map('intval', $_SESSION['product_compare']));
    if (!empty($_SESSION['product_compare'])) {
        setcookie('pc_compare_ids', $pc_cookie_value, time() + (30 * 24 * 60 * 60), '/', '', true, false);
    } else {
        setcookie('pc_compare_ids', '', time() - 3600, '/', '', true, false);
    }
    xtc_redirect(xtc_href_link('product_compare.php'));
}

// Action: clear list
if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    $_SESSION['product_compare'] = array();
    // v1.9.1: Cookie löschen - WICHTIG! Ohne dies stellt header_body die Session wieder her
    setcookie('pc_compare_ids', '', time() - 3600, '/', '', true, false);
    unset($_COOKIE['pc_compare_ids']);
    xtc_redirect(xtc_href_link('product_compare.php'));
}

// Breadcrumb
$bc_title = defined('PC_PAGE_TITLE') ? PC_PAGE_TITLE : 'Produktvergleich';
$breadcrumb->add($bc_title, xtc_href_link('product_compare.php'));

// Load the shop's product class (same as used by product listing)
require_once(DIR_WS_CLASSES . 'product.php');

// Load products using the shop's product class
$compare_products = array();

if (!empty($_SESSION['product_compare'])) {
    // Create product object (without pID, we call buildDataArray per product)
    $product_obj = new product();
    
    // Use the same SELECT fields as the product class default_select
    $select_fields = ADD_SELECT_PRODUCT .
                     'p.products_fsk18,
                      p.products_id,
                      p.products_price,
                      p.products_tax_class_id,
                      p.products_image,
                      p.products_quantity,
                      p.products_shippingtime,
                      p.products_vpe,
                      p.products_vpe_status,
                      p.products_vpe_value,
                      p.products_model,
                      p.manufacturers_id,
                      pd.products_name,
                      pd.products_heading_title,
                      pd.products_short_description';
    
    $id_counter = 0;
    foreach ($_SESSION['product_compare'] as $pid) {
        $pid = (int)$pid;
        
        // Query product data - same structure as product listing queries
        $product_query = xtDBquery("SELECT " . $select_fields . "
                                      FROM " . TABLE_PRODUCTS . " p
                                      JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd 
                                           ON pd.products_id = p.products_id
                                              AND pd.language_id = '" . (int)$_SESSION['languages_id'] . "'
                                              AND trim(pd.products_name) != ''
                                     WHERE p.products_status = '1'
                                       AND p.products_id = '" . $pid . "'
                                           " . PRODUCTS_CONDITIONS_P);
        
        if (xtc_db_num_rows($product_query, true)) {
            $product_data = xtc_db_fetch_array($product_query, true);
            $product_data['ID'] = $id_counter;
            
            // buildDataArray expects a DB result array by reference
            // It returns the complete productData array with all listing variables
            $listing_data = $product_obj->buildDataArray($product_data, 'thumbnail');
            
            // Add our custom remove link
            $listing_data['PRODUCTS_REMOVE_LINK'] = xtc_href_link('product_compare.php', 'action=remove&products_id=' . $pid);
            
            // Get manufacturer name if available
            if (!empty($product_data['manufacturers_id'])) {
                $mfr_query = xtc_db_query("SELECT manufacturers_name FROM " . TABLE_MANUFACTURERS . " WHERE manufacturers_id = '" . (int)$product_data['manufacturers_id'] . "'");
                if ($mfr = xtc_db_fetch_array($mfr_query)) {
                    $listing_data['MANUFACTURER'] = $mfr['manufacturers_name'];
                }
            }
            
            $compare_products[] = $listing_data;
            $id_counter++;
        }
    }
}

// Smarty assignments
$smarty->assign('COMPARE_PRODUCTS', $compare_products);
$smarty->assign('COMPARE_COUNT', count($compare_products));
$smarty->assign('COMPARE_MAX', (defined('MODULE_PRODUCT_COMPARE_MAX_PRODUCTS') ? (int)MODULE_PRODUCT_COMPARE_MAX_PRODUCTS : 6));
$smarty->assign('CLEAR_LINK', xtc_href_link('product_compare.php', 'action=clear'));
$smarty->assign('SHOP_LINK', xtc_href_link(FILENAME_DEFAULT));

// Text assignments
$smarty->assign('PC_PAGE_TITLE', defined('PC_PAGE_TITLE') ? PC_PAGE_TITLE : 'Produktvergleich');
$smarty->assign('PC_EMPTY_TEXT', defined('PC_EMPTY_TEXT') ? PC_EMPTY_TEXT : 'Keine Produkte zum Vergleichen.');
$smarty->assign('PC_EMPTY_HINT', defined('PC_EMPTY_HINT') ? PC_EMPTY_HINT : 'Produkte ueber den Vergleichen-Button hinzufuegen.');
$smarty->assign('PC_CLEAR_BUTTON', defined('PC_CLEAR_BUTTON') ? PC_CLEAR_BUTTON : 'Liste leeren');
$smarty->assign('PC_BACK_BUTTON', defined('PC_BACK_BUTTON') ? PC_BACK_BUTTON : 'Weiter einkaufen');
$smarty->assign('PC_REMOVE_BUTTON', defined('PC_REMOVE_BUTTON') ? PC_REMOVE_BUTTON : 'Entfernen');

// Fetch main content template
$smarty->assign('language', $_SESSION['language']);
$smarty->caching = false;

$main_content = $smarty->fetch(CURRENT_TEMPLATE . '/module/product_compare.html');

// Header (loads ALL box modules: cart, wishlist, search, languages, categories, footer etc.)
// MUST be called AFTER fetch and BEFORE display - same pattern as seedfinder.php
// Note: header.php triggers autoinclude header_head/ which sets $meta_title and $meta_description
require(DIR_WS_INCLUDES . 'header.php');

// Display page using index.html (fullcontent = no sidebar)
$smarty->assign('main_content', $main_content);
$smarty->assign('fullcontent', true);
$smarty->caching = 0;

if (!defined('RM')) {
    $smarty->load_filter('output', 'note');
}

$smarty->display(CURRENT_TEMPLATE . '/index.html');

// Footer
require('includes/application_bottom.php');
