<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v1.4.0 - Comparison Page (3-Column Card Layout)
   File: product_compare.php (shoproot)
   
   Changes v1.4.0:
   - fullcontent = true (no sidebar)
   - 3-column card layout instead of table
   - Short description instead of tags display
   - Proper header.php integration for navigation
   
   @author    Mr. Hanf / Manus AI
   @version   1.4.1
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

// Load products
$compare_products = array();
$all_filter_names = array();

if (!empty($_SESSION['product_compare'])) {
    foreach ($_SESSION['product_compare'] as $pid) {
        $product_query = xtc_db_query(
            "SELECT p.products_id, p.products_price, p.products_tax_class_id, 
                    p.products_image, p.products_model, p.products_weight,
                    p.products_quantity, p.manufacturers_id,
                    pd.products_name, pd.products_short_description,
                    m.manufacturers_name
               FROM " . TABLE_PRODUCTS . " p
               JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON p.products_id = pd.products_id
               LEFT JOIN " . TABLE_MANUFACTURERS . " m ON p.manufacturers_id = m.manufacturers_id
              WHERE p.products_id = '" . (int)$pid . "'
                AND pd.language_id = '" . (int)$_SESSION['languages_id'] . "'
                AND p.products_status = 1"
        );

        if ($product = xtc_db_fetch_array($product_query)) {
            // Price
            $tax_rate = xtc_get_tax_rate($product['products_tax_class_id']);
            if (isset($_SESSION['customers_status']['customers_status_show_price_tax']) 
                && $_SESSION['customers_status']['customers_status_show_price_tax'] == 1) {
                $price_display = $product['products_price'] * (1 + $tax_rate / 100);
            } else {
                $price_display = $product['products_price'];
            }
            $formatted_price = '';
            if (isset($xtPrice) && is_object($xtPrice) && method_exists($xtPrice, 'xtcFormat')) {
                $formatted_price = $xtPrice->xtcFormat($price_display, true);
            } else {
                $formatted_price = number_format($price_display, 2, ',', '.') . ' EUR';
            }

            // Image
            $image = '';
            if (!empty($product['products_image'])) {
                $image = DIR_WS_IMAGES . 'product_images/thumbnail_images/' . $product['products_image'];
            }

            // Links
            $link = xtc_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $pid);
            $remove_link = xtc_href_link('product_compare.php', 'action=remove&products_id=' . $pid);

            // Short description
            $short_desc = '';
            if (!empty($product['products_short_description'])) {
                $short_desc = $product['products_short_description'];
            }

            // Filter properties (products_tags)
            $filter_properties = array();
            $tags_query = xtc_db_query(
                "SELECT DISTINCT pto.options_name, pto.options_id, pto.sort_order as opt_sort,
                        ptv.values_name, ptv.values_description, ptv.values_image,
                        ptv.sort_order as val_sort
                   FROM " . TABLE_PRODUCTS_TAGS . " pt
                   JOIN " . TABLE_PRODUCTS_TAGS_OPTIONS . " pto ON pt.options_id = pto.options_id
                   JOIN " . TABLE_PRODUCTS_TAGS_VALUES . " ptv ON pt.values_id = ptv.values_id
                  WHERE pt.products_id = '" . (int)$pid . "'
                    AND pto.languages_id = '" . (int)$_SESSION['languages_id'] . "'
                    AND ptv.languages_id = '" . (int)$_SESSION['languages_id'] . "'
               ORDER BY pto.sort_order, ptv.sort_order"
            );

            while ($tag = xtc_db_fetch_array($tags_query)) {
                $filter_name = $tag['options_name'];

                if (!isset($all_filter_names[$filter_name])) {
                    $all_filter_names[$filter_name] = $tag['opt_sort'];
                }

                if (!isset($filter_properties[$filter_name])) {
                    $filter_properties[$filter_name] = array();
                }

                $value_image_url = '';
                if (!empty($tag['values_image'])) {
                    if (strpos($tag['values_image'], 'http') === 0) {
                        $value_image_url = $tag['values_image'];
                    } else {
                        $value_image_url = DIR_WS_CATALOG . 'images/tags/' . $tag['values_image'];
                    }
                }

                $filter_properties[$filter_name][] = array(
                    'name' => $tag['values_name'],
                    'description' => $tag['values_description'],
                    'image' => $value_image_url
                );
            }

            $compare_products[] = array(
                'PRODUCTS_ID' => $pid,
                'PRODUCTS_NAME' => $product['products_name'],
                'PRODUCTS_MODEL' => $product['products_model'],
                'PRODUCTS_PRICE' => $formatted_price,
                'PRODUCTS_WEIGHT' => $product['products_weight'],
                'PRODUCTS_IMAGE' => $image,
                'PRODUCTS_LINK' => $link,
                'PRODUCTS_REMOVE_LINK' => $remove_link,
                'PRODUCTS_SHORT_DESCRIPTION' => $short_desc,
                'MANUFACTURERS_NAME' => $product['manufacturers_name'],
                'PRODUCTS_QUANTITY' => $product['products_quantity'],
                'FILTER_PROPERTIES' => $filter_properties
            );
        }
    }
}

// Sort filter names
asort($all_filter_names);

// include header (loads template framework, navigation, CSS, etc.)
require(DIR_WS_INCLUDES . 'header.php');

// Smarty assignments
$smarty->assign('COMPARE_PRODUCTS', $compare_products);
$smarty->assign('COMPARE_COUNT', count($compare_products));
$smarty->assign('COMPARE_MAX', (defined('MODULE_PRODUCT_COMPARE_MAX_PRODUCTS') ? (int)MODULE_PRODUCT_COMPARE_MAX_PRODUCTS : 6));
$smarty->assign('ALL_FILTER_NAMES', array_keys($all_filter_names));
$smarty->assign('CLEAR_LINK', xtc_href_link('product_compare.php', 'action=clear'));
$smarty->assign('SHOP_LINK', xtc_href_link(FILENAME_DEFAULT));

// Text assignments
$smarty->assign('PC_PAGE_TITLE', defined('PC_PAGE_TITLE') ? PC_PAGE_TITLE : 'Produktvergleich');
$smarty->assign('PC_EMPTY_TEXT', defined('PC_EMPTY_TEXT') ? PC_EMPTY_TEXT : 'Keine Produkte zum Vergleichen.');
$smarty->assign('PC_EMPTY_HINT', defined('PC_EMPTY_HINT') ? PC_EMPTY_HINT : 'Produkte ueber den Vergleichen-Button hinzufuegen.');
$smarty->assign('PC_CLEAR_BUTTON', defined('PC_CLEAR_BUTTON') ? PC_CLEAR_BUTTON : 'Liste leeren');
$smarty->assign('PC_BACK_BUTTON', defined('PC_BACK_BUTTON') ? PC_BACK_BUTTON : 'Weiter einkaufen');
$smarty->assign('PC_REMOVE_BUTTON', defined('PC_REMOVE_BUTTON') ? PC_REMOVE_BUTTON : 'Entfernen');
$smarty->assign('PC_DETAILS_BUTTON', defined('PC_DETAILS_BUTTON') ? PC_DETAILS_BUTTON : 'Details ansehen');
$smarty->assign('PC_ADD_TO_CART', defined('PC_ADD_TO_CART') ? PC_ADD_TO_CART : 'In den Warenkorb');
$smarty->assign('PC_ROW_PRODUCT', defined('PC_ROW_PRODUCT') ? PC_ROW_PRODUCT : 'Produkt');
$smarty->assign('PC_ROW_MANUFACTURER', defined('PC_ROW_MANUFACTURER') ? PC_ROW_MANUFACTURER : 'Hersteller');
$smarty->assign('PC_ROW_PRICE', defined('PC_ROW_PRICE') ? PC_ROW_PRICE : 'Preis');
$smarty->assign('PC_ROW_WEIGHT', defined('PC_ROW_WEIGHT') ? PC_ROW_WEIGHT : 'Gewicht');
$smarty->assign('PC_ROW_AVAILABILITY', defined('PC_ROW_AVAILABILITY') ? PC_ROW_AVAILABILITY : 'Verfuegbarkeit');
$smarty->assign('PC_IN_STOCK', defined('PC_IN_STOCK') ? PC_IN_STOCK : 'Auf Lager');
$smarty->assign('PC_OUT_OF_STOCK', defined('PC_OUT_OF_STOCK') ? PC_OUT_OF_STOCK : 'Nicht verfuegbar');
$smarty->assign('PC_NO_DATA', defined('PC_NO_DATA') ? PC_NO_DATA : '-');

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
