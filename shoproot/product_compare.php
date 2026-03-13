<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v1.6.0 - Comparison Page (Original Listing Design)
   File: product_compare.php (shoproot)
   
   Changes v1.6.0:
   - Uses the shop's own product class to generate listing data
   - Generates the SAME Smarty variables as product_listing_include.html
   - PRODUCT_ATTRIBUTES rendered by the shop (multi_options_1.html)
   - PRODUCTS_ADD_CART_BUTTON, PRODUCTS_BUTTON_DETAILS from shop
   - FORM_ACTION / FORM_END for cart forms
   - PRODUCTS_PRICE_ARRAY for price_box.html
   - PRODUCTS_TAX_INFO, PRODUCTS_SHIPPING_LINK
   - ADD_QTY / ADD_QTYPD for quantity selection
   - Short description with max-height 600px
   
   @author    Mr. Hanf / Manus AI
   @version   1.6.0
   @date      2026-03-13
   -----------------------------------------------------------------------------------------*/

require('includes/application_top.php');

// create smarty instance (required before header.php)
$smarty = new Smarty;

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
    xtc_redirect(xtc_href_link('product_compare.php'));
}

// Action: clear list
if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    $_SESSION['product_compare'] = array();
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
    $product_obj = new product();
    
    foreach ($_SESSION['product_compare'] as $pid) {
        $pid = (int)$pid;
        
        // Check product exists and is active
        $check_query = xtc_db_query(
            "SELECT products_id FROM " . TABLE_PRODUCTS . "
              WHERE products_id = '" . $pid . "'
                AND products_status = 1"
        );
        if (!xtc_db_num_rows($check_query)) continue;
        
        // Use the shop's product class to build listing data
        // This generates ALL the same variables as product_listing_include.html
        $product_data = $product_obj->buildDataArray($pid);
        
        // Add our custom fields
        $product_data['PRODUCTS_REMOVE_LINK'] = xtc_href_link('product_compare.php', 'action=remove&products_id=' . $pid);
        
        $compare_products[] = $product_data;
    }
}

// include header (loads template framework, navigation, CSS, etc.)
require(DIR_WS_INCLUDES . 'header.php');

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

// Template - use fullcontent mode (no sidebar)
$smarty->assign('language', $_SESSION['language']);
$smarty->caching = false;

$main_content = $smarty->fetch(CURRENT_TEMPLATE . '/module/product_compare.html');

$smarty->assign('main_content', $main_content);
$smarty->assign('language', $_SESSION['language']);
$smarty->assign('fullcontent', true);

if (!defined('RM'))
    $smarty->load_filter('output', 'note');
$smarty->display(CURRENT_TEMPLATE . '/index.html');

require('includes/application_bottom.php');
?>
