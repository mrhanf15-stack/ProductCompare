<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v1.2.2 - AJAX Endpoint
   
   Hookpoint: includes/extra/ajax/
   
   modified eCommerce lädt diese Datei über ajax.php?ext=product_compare
   Die ajax.php inkludiert die Datei direkt und ruft dann die Funktion product_compare() auf.
   
   Verarbeitet AJAX-Anfragen für den Produktvergleich:
   - add: Produkt zur Vergleichsliste hinzufügen
   - remove: Produkt aus der Vergleichsliste entfernen
   - clear: Vergleichsliste leeren
   - list: Aktuelle Vergleichsliste zurückgeben
   - resolve_sku: SKU/Artikelnummer → products_id auflösen
   - resolve_url: SEO-URL → products_id auflösen
   
   @author    Mr. Hanf / Manus AI
   @version   1.2.2
   @date      2026-03-12
   -----------------------------------------------------------------------------------------*/

// modified eCommerce ajax.php ruft die Funktion mit dem gleichen Namen wie die Datei auf
function product_compare() {
    
    // Vergleichsliste initialisieren
    if (!isset($_SESSION['product_compare'])) {
        $_SESSION['product_compare'] = array();
    }
    
    // Maximale Anzahl Produkte
    $max_products = (defined('MODULE_PRODUCT_COMPARE_MAX_PRODUCTS')) 
        ? (int)MODULE_PRODUCT_COMPARE_MAX_PRODUCTS 
        : 6;
    
    $sub_action = isset($_GET['sub_action']) ? $_GET['sub_action'] : '';
    $product_id = isset($_GET['products_id']) ? (int)$_GET['products_id'] : 0;
    
    $response = array(
        'success' => false,
        'message' => '',
        'count' => count($_SESSION['product_compare']),
        'products' => $_SESSION['product_compare'],
        'max' => $max_products
    );
    
    switch ($sub_action) {
        
        case 'add':
            if ($product_id > 0) {
                $check_query = xtc_db_query("SELECT products_id FROM " . TABLE_PRODUCTS . " WHERE products_id = '" . $product_id . "' AND products_status = 1");
                if (xtc_db_num_rows($check_query) > 0) {
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
                    $response['message'] = 'product_not_found';
                }
            }
            break;
            
        case 'remove':
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
            break;
            
        case 'clear':
            $_SESSION['product_compare'] = array();
            $response['success'] = true;
            $response['message'] = 'cleared';
            break;
            
        case 'list':
            $response['success'] = true;
            $response['message'] = 'list';
            
            if (!empty($_SESSION['product_compare'])) {
                $product_details = array();
                foreach ($_SESSION['product_compare'] as $pid) {
                    $pq = xtc_db_query("SELECT p.products_id, p.products_image, pd.products_name 
                                        FROM " . TABLE_PRODUCTS . " p 
                                        JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON p.products_id = pd.products_id 
                                        WHERE p.products_id = '" . (int)$pid . "' 
                                        AND pd.language_id = '" . (int)$_SESSION['languages_id'] . "'");
                    if ($pr = xtc_db_fetch_array($pq)) {
                        $image = '';
                        if (!empty($pr['products_image'])) {
                            $image = 'images/product_images/thumbnail_images/' . $pr['products_image'];
                        }
                        $product_details[] = array(
                            'id' => $pr['products_id'],
                            'name' => $pr['products_name'],
                            'image' => $image
                        );
                    }
                }
                $response['product_details'] = $product_details;
            }
            break;
        
        // SKU → products_id auflösen (für Seedfinder-Karten)
        case 'resolve_sku':
            $sku = isset($_GET['sku']) ? trim($_GET['sku']) : '';
            if (!empty($sku)) {
                $sku_query = xtc_db_query(
                    "SELECT products_id FROM " . TABLE_PRODUCTS . " 
                     WHERE products_model = '" . xtc_db_input($sku) . "' 
                     AND products_status = 1 
                     LIMIT 1"
                );
                if ($row = xtc_db_fetch_array($sku_query)) {
                    $response['success'] = true;
                    $response['products_id'] = (int)$row['products_id'];
                    $response['message'] = 'resolved';
                } else {
                    $response['message'] = 'sku_not_found';
                }
            } else {
                $response['message'] = 'empty_sku';
            }
            break;
        
        // SEO-URL → products_id auflösen
        case 'resolve_url':
            $product_url = isset($_GET['product_url']) ? trim($_GET['product_url']) : '';
            if (!empty($product_url)) {
                $url_parts = parse_url($product_url);
                $path = isset($url_parts['path']) ? $url_parts['path'] : '';
                $path = rtrim($path, '/');
                $segments = explode('/', $path);
                $slug = end($segments);
                
                if (!empty($slug)) {
                    $found = false;
                    
                    // Methode 1: products_seo Tabelle
                    $seo_query = @xtc_db_query(
                        "SELECT products_id FROM products_seo 
                         WHERE url_text = '" . xtc_db_input($slug) . "' 
                         LIMIT 1"
                    );
                    if ($seo_query && $row = xtc_db_fetch_array($seo_query)) {
                        $response['success'] = true;
                        $response['products_id'] = (int)$row['products_id'];
                        $response['message'] = 'resolved';
                        $found = true;
                    }
                    
                    // Methode 2: URL-Alias Tabelle
                    if (!$found) {
                        $alias_query = @xtc_db_query(
                            "SELECT url_id FROM url_alias 
                             WHERE url_text LIKE '%" . xtc_db_input($slug) . "%' 
                             AND url_type = 'product'
                             LIMIT 1"
                        );
                        if ($alias_query && $row = xtc_db_fetch_array($alias_query)) {
                            $response['success'] = true;
                            $response['products_id'] = (int)$row['url_id'];
                            $response['message'] = 'resolved';
                            $found = true;
                        }
                    }
                    
                    // Methode 3: products_model
                    if (!$found) {
                        $model_query = xtc_db_query(
                            "SELECT products_id FROM " . TABLE_PRODUCTS . " 
                             WHERE products_model LIKE '%" . xtc_db_input($slug) . "%' 
                             AND products_status = 1 
                             LIMIT 1"
                        );
                        if ($row = xtc_db_fetch_array($model_query)) {
                            $response['success'] = true;
                            $response['products_id'] = (int)$row['products_id'];
                            $response['message'] = 'resolved';
                            $found = true;
                        }
                    }
                    
                    // Methode 4: products_name
                    if (!$found) {
                        $search_name = str_replace('-', ' ', $slug);
                        $name_query = xtc_db_query(
                            "SELECT p.products_id 
                             FROM " . TABLE_PRODUCTS . " p 
                             JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON p.products_id = pd.products_id 
                             WHERE pd.products_name LIKE '%" . xtc_db_input($search_name) . "%' 
                             AND pd.language_id = '" . (int)$_SESSION['languages_id'] . "'
                             AND p.products_status = 1 
                             LIMIT 1"
                        );
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
            break;
            
        default:
            $response['message'] = 'invalid_action';
            break;
    }
    
    // Aktualisierte Werte
    $response['count'] = count($_SESSION['product_compare']);
    $response['products'] = array_values($_SESSION['product_compare']);
    
    return $response;
}
?>
