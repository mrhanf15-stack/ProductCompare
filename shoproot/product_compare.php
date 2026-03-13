<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v1.5.0 - Comparison Page (Original Listing Design)
   File: product_compare.php (shoproot)
   
   Changes v1.5.0:
   - Uses original product listing card design
   - Loads product options/attributes for each product
   - Warenkorb (Add to Cart) form support
   - Details button and Buy Now button
   - Short description display
   - 3-column layout (col-lg-4)
   
   @author    Mr. Hanf / Manus AI
   @version   1.5.0
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

// Action: add to cart
if (isset($_POST['action']) && $_POST['action'] == 'buy_now' && isset($_POST['products_id'])) {
    $buy_pid = (int)$_POST['products_id'];
    $buy_qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
    if ($buy_qty < 1) $buy_qty = 1;
    
    // Collect attribute selections
    $id_array = array();
    if (isset($_POST['id']) && is_array($_POST['id'])) {
        $id_array = $_POST['id'];
    }
    
    // Use the shop cart
    if (isset($_SESSION['cart']) && is_object($_SESSION['cart'])) {
        $_SESSION['cart']->add_cart($buy_pid, $buy_qty, $id_array);
    }
    
    xtc_redirect(xtc_href_link(FILENAME_SHOPPING_CART));
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

if (!empty($_SESSION['product_compare'])) {
    foreach ($_SESSION['product_compare'] as $pid) {
        $product_query = xtc_db_query(
            "SELECT p.products_id, p.products_price, p.products_tax_class_id, 
                    p.products_image, p.products_model, p.products_weight,
                    p.products_quantity, p.manufacturers_id,
                    p.products_shippingtime,
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
            $buy_link = xtc_href_link('product_compare.php', '', 'SSL');

            // Manufacturer link
            $manufacturer_link = '';
            if (!empty($product['manufacturers_id'])) {
                $manufacturer_link = xtc_href_link(FILENAME_DEFAULT, 'manufacturers_id=' . (int)$product['manufacturers_id']);
            }

            // Short description
            $short_desc = '';
            if (!empty($product['products_short_description'])) {
                $short_desc = $product['products_short_description'];
            }

            // Shipping name
            $shipping_name = '';
            $shipping_time = (int)$product['products_shippingtime'];
            if ($shipping_time > 0) {
                $ship_query = xtc_db_query(
                    "SELECT shipping_status_name FROM " . TABLE_SHIPPING_STATUS . "
                      WHERE shipping_status_id = '" . $shipping_time . "'
                        AND language_id = '" . (int)$_SESSION['languages_id'] . "'"
                );
                if ($ship = xtc_db_fetch_array($ship_query)) {
                    $shipping_name = $ship['shipping_status_name'];
                }
            }

            // Product options/attributes
            $product_options = array();
            $has_attributes = false;
            $options_query = xtc_db_query(
                "SELECT DISTINCT po.products_options_id, po.products_options_name
                   FROM " . TABLE_PRODUCTS_OPTIONS . " po
                   JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa ON po.products_options_id = pa.options_id
                  WHERE pa.products_id = '" . (int)$pid . "'
                    AND po.language_id = '" . (int)$_SESSION['languages_id'] . "'
                 ORDER BY po.products_options_sort_order, po.products_options_name"
            );
            
            while ($option = xtc_db_fetch_array($options_query)) {
                $has_attributes = true;
                $option_values = array();
                
                $values_query = xtc_db_query(
                    "SELECT pa.products_attributes_id, pa.options_values_id,
                            pa.options_values_price, pa.price_prefix,
                            pa.attributes_stock, pa.attributes_model,
                            pov.products_options_values_name,
                            pa.sortorder
                       FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                       JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov 
                            ON pa.options_values_id = pov.products_options_values_id
                      WHERE pa.products_id = '" . (int)$pid . "'
                        AND pa.options_id = '" . (int)$option['products_options_id'] . "'
                        AND pov.language_id = '" . (int)$_SESSION['languages_id'] . "'
                   ORDER BY pa.sortorder, pov.products_options_values_name"
                );
                
                while ($value = xtc_db_fetch_array($values_query)) {
                    $attr_price = '';
                    $attr_full_price = '';
                    $attr_stock = (int)$value['attributes_stock'];
                    
                    if ((float)$value['options_values_price'] > 0) {
                        $opt_price_raw = (float)$value['options_values_price'];
                        if (isset($_SESSION['customers_status']['customers_status_show_price_tax']) 
                            && $_SESSION['customers_status']['customers_status_show_price_tax'] == 1) {
                            $opt_price_display = $opt_price_raw * (1 + $tax_rate / 100);
                        } else {
                            $opt_price_display = $opt_price_raw;
                        }
                        
                        if ($value['price_prefix'] == '=') {
                            // Fixed price
                            if (isset($xtPrice) && is_object($xtPrice)) {
                                $attr_full_price = $xtPrice->xtcFormat($opt_price_display, true);
                            } else {
                                $attr_full_price = number_format($opt_price_display, 2, ',', '.') . ' EUR';
                            }
                        } else {
                            if (isset($xtPrice) && is_object($xtPrice)) {
                                $attr_price = $value['price_prefix'] . ' ' . $xtPrice->xtcFormat($opt_price_display, true);
                            } else {
                                $attr_price = $value['price_prefix'] . ' ' . number_format($opt_price_display, 2, ',', '.') . ' EUR';
                            }
                        }
                    }
                    
                    // Calculate full price for JSON data
                    $base_price = (float)$product['products_price'];
                    if (isset($_SESSION['customers_status']['customers_status_show_price_tax']) 
                        && $_SESSION['customers_status']['customers_status_show_price_tax'] == 1) {
                        $base_price = $base_price * (1 + $tax_rate / 100);
                    }
                    $opt_price_val = (float)$value['options_values_price'];
                    if (isset($_SESSION['customers_status']['customers_status_show_price_tax']) 
                        && $_SESSION['customers_status']['customers_status_show_price_tax'] == 1) {
                        $opt_price_val = $opt_price_val * (1 + $tax_rate / 100);
                    }
                    
                    $json_data = array(
                        'pid' => (int)$pid,
                        'prefix' => $value['price_prefix'],
                        'aprice' => round($opt_price_val, 2),
                        'gprice' => round($base_price, 2),
                        'oprice' => round($base_price, 2)
                    );
                    
                    $option_values[] = array(
                        'ID' => $value['products_attributes_id'],
                        'VALUES_ID' => $value['options_values_id'],
                        'TEXT' => $value['products_options_values_name'],
                        'PRICE' => $attr_price,
                        'FULL_PRICE' => $attr_full_price,
                        'PREFIX' => $value['price_prefix'],
                        'STOCK' => $attr_stock,
                        'JSON_ATTRDATA' => htmlspecialchars(json_encode($json_data), ENT_QUOTES, 'UTF-8')
                    );
                }
                
                $product_options[] = array(
                    'ID' => $option['products_options_id'],
                    'NAME' => $option['products_options_name'],
                    'DATA' => $option_values
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
                'PRODUCTS_BUY_LINK' => $buy_link,
                'PRODUCTS_SHORT_DESCRIPTION' => $short_desc,
                'MANUFACTURERS_NAME' => $product['manufacturers_name'],
                'MANUFACTURERS_LINK' => $manufacturer_link,
                'PRODUCTS_QUANTITY' => $product['products_quantity'],
                'PRODUCTS_SHIPPINGTIME' => $shipping_time,
                'SHIPPING_NAME' => $shipping_name,
                'HAS_ATTRIBUTES' => $has_attributes,
                'PRODUCT_OPTIONS' => $product_options
            );
        }
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
$smarty->assign('PC_DETAILS_BUTTON', defined('PC_DETAILS_BUTTON') ? PC_DETAILS_BUTTON : 'Details');
$smarty->assign('PC_ADD_TO_CART', defined('PC_ADD_TO_CART') ? PC_ADD_TO_CART : 'In den Warenkorb');

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
