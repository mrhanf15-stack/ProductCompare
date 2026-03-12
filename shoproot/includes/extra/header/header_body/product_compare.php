<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v1.0.0 - Header Body Hookpoint
   
   Hookpoint: includes/extra/header/header_body/
   
   Lädt JavaScript und CSS für den Produktvergleich auf allen Seiten.
   Zeigt den Floating-Badge mit der Anzahl der Vergleichsprodukte.
   
   @author    Mr. Hanf / Manus AI
   @version   1.0.0
   @date      2026-03-12
   -----------------------------------------------------------------------------------------*/

if (defined('MODULE_PRODUCT_COMPARE_STATUS') && MODULE_PRODUCT_COMPARE_STATUS == 'true') {
    
    // Session initialisieren
    if (!isset($_SESSION['product_compare'])) {
        $_SESSION['product_compare'] = array();
    }
    
    $pc_count = count($_SESSION['product_compare']);
    $pc_max = (defined('MODULE_PRODUCT_COMPARE_MAX_PRODUCTS')) ? (int)MODULE_PRODUCT_COMPARE_MAX_PRODUCTS : 6;
    $pc_compare_url = xtc_href_link('product_compare.php');
    
    // Sprachdatei laden
    if (file_exists(DIR_FS_LANGUAGES . $_SESSION['language'] . '/extra/product_compare.php')) {
        require_once(DIR_FS_LANGUAGES . $_SESSION['language'] . '/extra/product_compare.php');
    }
    
    // Floating Compare Badge HTML
    echo '<div id="product-compare-badge" class="product-compare-badge' . ($pc_count > 0 ? ' active' : '') . '" title="' . (defined('PC_BADGE_TITLE') ? PC_BADGE_TITLE : 'Produktvergleich') . '">';
    echo '  <a href="' . $pc_compare_url . '" class="compare-badge-link">';
    echo '    <span class="fa fa-balance-scale"></span>';
    echo '    <span class="compare-count">' . $pc_count . '</span>';
    echo '  </a>';
    echo '</div>';
}
?>
