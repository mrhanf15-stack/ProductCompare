<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v1.0.0 - AJAX Endpoint
   
   Hookpoint: includes/extra/ajax/
   
   Verarbeitet AJAX-Anfragen für den Produktvergleich:
   - add: Produkt zur Vergleichsliste hinzufügen
   - remove: Produkt aus der Vergleichsliste entfernen
   - clear: Vergleichsliste leeren
   - list: Aktuelle Vergleichsliste zurückgeben
   
   @author    Mr. Hanf / Manus AI
   @version   1.0.0
   @date      2026-03-12
   -----------------------------------------------------------------------------------------*/

if (isset($_GET['action']) && $_GET['action'] == 'product_compare') {
    
    // Session starten falls nötig
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
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
                // Prüfe ob Produkt existiert
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
            
            // Produktdetails laden für die Mini-Vorschau
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
            
        default:
            $response['message'] = 'invalid_action';
            break;
    }
    
    // Aktualisierte Werte
    $response['count'] = count($_SESSION['product_compare']);
    $response['products'] = array_values($_SESSION['product_compare']);
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
