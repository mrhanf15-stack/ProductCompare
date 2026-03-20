<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v1.9.1 - AJAX Endpoint
   Hookpoint: includes/extra/ajax/
   Loaded via: ajax.php?ext=product_compare

   v1.9.1: BUGFIX - Clear-Action löscht auch das Cookie 'pc_compare_ids'
           - Cookie-Restore wird bei clear-Action übersprungen
           - Remove-Action synchronisiert Cookie serverseitig
   v1.9.0: Cookie-basierte Persistenz
           - Bei AJAX-Aufrufen ebenfalls Cookie-Restore durchführen
           - Stellt sicher, dass nach Logout+Login auch AJAX-Calls die Liste kennen

   @author    Mr. Hanf / Manus AI
   @version   1.9.1
   @date      2026-03-20
   -----------------------------------------------------------------------------------------*/

function product_compare() {

    if (!isset($_SESSION['product_compare'])) {
        $_SESSION['product_compare'] = array();
    }

    // sub_action frühzeitig lesen (für Cookie-Restore-Entscheidung)
    $sub_action = '';
    if (isset($_GET['sub_action'])) {
        $sub_action = $_GET['sub_action'];
    } elseif (isset($_POST['sub_action'])) {
        $sub_action = $_POST['sub_action'];
    }

    // === Cookie-Restore auch bei AJAX-Aufrufen ===
    // Falls die Session leer ist aber der Cookie noch Daten hat (nach Logout+Login)
    // v1.9.1: NICHT bei clear-Action - sonst wird die geleerte Session sofort wieder befüllt
    if ($sub_action !== 'clear' && empty($_SESSION['product_compare']) && isset($_COOKIE['pc_compare_ids']) && $_COOKIE['pc_compare_ids'] !== '') {
        $pc_cookie_raw = $_COOKIE['pc_compare_ids'];
        $pc_cookie_ids = array_map('intval', explode(',', $pc_cookie_raw));
        $pc_cookie_ids = array_filter($pc_cookie_ids, function($id) { return $id > 0; });
        $pc_cookie_ids = array_unique($pc_cookie_ids);

        if (!empty($pc_cookie_ids)) {
            $pc_ids_str = implode(',', array_map('intval', $pc_cookie_ids));
            $pc_check_query = xtc_db_query(
                "SELECT products_id FROM " . TABLE_PRODUCTS .
                " WHERE products_id IN (" . $pc_ids_str . ") AND products_status = 1"
            );
            $pc_valid_ids = array();
            while ($pc_row = xtc_db_fetch_array($pc_check_query)) {
                $pc_valid_ids[] = (int)$pc_row['products_id'];
            }
            if (!empty($pc_valid_ids)) {
                $_SESSION['product_compare'] = array_values($pc_valid_ids);
            }
        }
    }

    $max_products = 6;
    if (defined('MODULE_PRODUCT_COMPARE_MAX_PRODUCTS')) {
        $max_products = (int)MODULE_PRODUCT_COMPARE_MAX_PRODUCTS;
    }

    $product_id = 0;
    if (isset($_GET['products_id'])) {
        $product_id = (int)$_GET['products_id'];
    } elseif (isset($_POST['products_id'])) {
        $product_id = (int)$_POST['products_id'];
    }

    $response = array(
        'success' => false,
        'message' => '',
        'count'   => count($_SESSION['product_compare']),
        'products' => $_SESSION['product_compare'],
        'max'     => $max_products
    );

    if ($sub_action == 'add') {
        if ($product_id > 0) {
            if (in_array($product_id, $_SESSION['product_compare'])) {
                $response['success'] = false;
                $response['message'] = 'already_in_list';
            } elseif (count($_SESSION['product_compare']) >= $max_products) {
                $response['success'] = false;
                $response['message'] = 'max_reached';
            } else {
                $_SESSION['product_compare'][] = $product_id;
                $response['success'] = true;
                $response['message'] = 'added';
            }
        } else {
            $response['message'] = 'invalid_product';
        }
    } elseif ($sub_action == 'remove') {
        if ($product_id > 0) {
            $key = array_search($product_id, $_SESSION['product_compare']);
            if ($key !== false) {
                unset($_SESSION['product_compare'][$key]);
                $_SESSION['product_compare'] = array_values($_SESSION['product_compare']);
                $response['success'] = true;
                $response['message'] = 'removed';
            } else {
                $response['message'] = 'not_in_list';
            }
        }
    } elseif ($sub_action == 'clear') {
        // v1.9.1: Session UND Cookie leeren
        $_SESSION['product_compare'] = array();
        setcookie('pc_compare_ids', '', time() - 3600, '/', '', true, false);
        unset($_COOKIE['pc_compare_ids']);
        $response['success'] = true;
        $response['message'] = 'cleared';
    } elseif ($sub_action == 'list') {
        $response['success'] = true;
        $response['message'] = 'list';
        if (!empty($_SESSION['product_compare'])) {
            $product_details = array();
            $ids = implode(',', array_map('intval', $_SESSION['product_compare']));
            $lang_id = isset($_SESSION['languages_id']) ? (int)$_SESSION['languages_id'] : 1;
            $pq = xtc_db_query("SELECT p.products_id, p.products_image, pd.products_name FROM " . TABLE_PRODUCTS . " p LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (p.products_id = pd.products_id AND pd.language_id = '" . $lang_id . "') WHERE p.products_id IN (" . $ids . ")");
            while ($pr = xtc_db_fetch_array($pq)) {
                $img = '';
                if (!empty($pr['products_image'])) {
                    $img = 'images/product_images/thumbnail_images/' . $pr['products_image'];
                }
                $product_details[] = array(
                    'id'    => (int)$pr['products_id'],
                    'name'  => $pr['products_name'],
                    'image' => $img
                );
            }
            $response['product_details'] = $product_details;
        }
    } elseif ($sub_action == 'resolve_sku') {
        $sku = isset($_GET['sku']) ? trim($_GET['sku']) : '';
        if ($sku != '') {
            $sq = xtc_db_query("SELECT products_id FROM " . TABLE_PRODUCTS . " WHERE products_model = '" . xtc_db_input($sku) . "' AND products_status = 1 LIMIT 1");
            if ($row = xtc_db_fetch_array($sq)) {
                $response['success'] = true;
                $response['products_id'] = (int)$row['products_id'];
                $response['message'] = 'resolved';
            } else {
                $response['message'] = 'sku_not_found';
            }
        } else {
            $response['message'] = 'empty_sku';
        }
    } elseif ($sub_action == 'resolve_url') {
        $product_url = isset($_GET['product_url']) ? trim($_GET['product_url']) : '';
        if ($product_url != '') {
            $url_parts = parse_url($product_url);
            $path = isset($url_parts['path']) ? $url_parts['path'] : '';
            $path = rtrim($path, '/');
            $segments = explode('/', $path);
            $slug = end($segments);
            if ($slug != '') {
                $found = false;
                $seo_query = @xtc_db_query("SELECT products_id FROM products_seo WHERE url_text = '" . xtc_db_input($slug) . "' LIMIT 1");
                if ($seo_query && $row = xtc_db_fetch_array($seo_query)) {
                    $response['success'] = true;
                    $response['products_id'] = (int)$row['products_id'];
                    $response['message'] = 'resolved';
                    $found = true;
                }
                if (!$found) {
                    $alias_query = @xtc_db_query("SELECT url_id FROM url_alias WHERE url_text LIKE '%" . xtc_db_input($slug) . "%' AND url_type = 'product' LIMIT 1");
                    if ($alias_query && $row = xtc_db_fetch_array($alias_query)) {
                        $response['success'] = true;
                        $response['products_id'] = (int)$row['url_id'];
                        $response['message'] = 'resolved';
                        $found = true;
                    }
                }
                if (!$found) {
                    $model_query = xtc_db_query("SELECT products_id FROM " . TABLE_PRODUCTS . " WHERE products_model LIKE '%" . xtc_db_input($slug) . "%' AND products_status = 1 LIMIT 1");
                    if ($row = xtc_db_fetch_array($model_query)) {
                        $response['success'] = true;
                        $response['products_id'] = (int)$row['products_id'];
                        $response['message'] = 'resolved';
                        $found = true;
                    }
                }
                if (!$found) {
                    $search_name = str_replace('-', ' ', $slug);
                    $lang_id = isset($_SESSION['languages_id']) ? (int)$_SESSION['languages_id'] : 1;
                    $name_query = xtc_db_query("SELECT p.products_id FROM " . TABLE_PRODUCTS . " p LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (p.products_id = pd.products_id AND pd.language_id = '" . $lang_id . "') WHERE pd.products_name LIKE '%" . xtc_db_input($search_name) . "%' AND p.products_status = 1 LIMIT 1");
                    if ($row = xtc_db_fetch_array($name_query)) {
                        $response['success'] = true;
                        $response['products_id'] = (int)$row['products_id'];
                        $response['message'] = 'resolved';
                        $found = true;
                    }
                }
                if (!$found) {
                    $response['message'] = 'url_not_resolved';
                }
            } else {
                $response['message'] = 'empty_slug';
            }
        } else {
            $response['message'] = 'empty_url';
        }
    } else {
        $response['message'] = 'invalid_action';
    }

    $response['count'] = count($_SESSION['product_compare']);
    $response['products'] = array_values($_SESSION['product_compare']);

    return $response;
}
?>
