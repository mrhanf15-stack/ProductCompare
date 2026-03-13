<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v1.8.1 - Header Body Hookpoint
   
   Hookpoint: includes/extra/header/header_body/
   
   Lädt JavaScript und CSS für den Produktvergleich auf allen Seiten.
   Zeigt den Floating-Badge mit der Anzahl der Vergleichsprodukte.
   
   v1.8.1: Badge-Position und Farben aus Admin-Konfiguration lesen
   v1.0.1: DIR_FS_LANGUAGES → DIR_WS_LANGUAGES (korrekte Konstante in modified)
   
   @author    Mr. Hanf / Manus AI
   @version   1.8.1
   @date      2026-03-13
   -----------------------------------------------------------------------------------------*/

if (defined('MODULE_PRODUCT_COMPARE_STATUS') && MODULE_PRODUCT_COMPARE_STATUS == 'true') {
    
    // Session initialisieren
    if (!isset($_SESSION['product_compare'])) {
        $_SESSION['product_compare'] = array();
    }
    
    $pc_count = count($_SESSION['product_compare']);
    $pc_max = (defined('MODULE_PRODUCT_COMPARE_MAX_PRODUCTS')) ? (int)MODULE_PRODUCT_COMPARE_MAX_PRODUCTS : 6;
    $pc_compare_url = xtc_href_link('product_compare.php');
    
    // Sprachdatei laden - DIR_WS_LANGUAGES ist die korrekte Konstante (definiert in includes/paths.php)
    $pc_lang_file = DIR_WS_LANGUAGES . $_SESSION['language'] . '/extra/product_compare.php';
    if (file_exists($pc_lang_file)) {
        require_once($pc_lang_file);
    }
    
    // Badge-Konfiguration aus Admin lesen
    $pc_badge_position = (defined('MODULE_PRODUCT_COMPARE_BADGE_POSITION')) ? MODULE_PRODUCT_COMPARE_BADGE_POSITION : 'bottom-right';
    $pc_badge_bg = (defined('MODULE_PRODUCT_COMPARE_BADGE_BG_COLOR')) ? MODULE_PRODUCT_COMPARE_BADGE_BG_COLOR : '#28a745';
    $pc_badge_text = (defined('MODULE_PRODUCT_COMPARE_BADGE_TEXT_COLOR')) ? MODULE_PRODUCT_COMPARE_BADGE_TEXT_COLOR : '#ffffff';
    $pc_badge_count_bg = (defined('MODULE_PRODUCT_COMPARE_BADGE_COUNT_BG_COLOR')) ? MODULE_PRODUCT_COMPARE_BADGE_COUNT_BG_COLOR : '#dc3545';
    $pc_badge_count_text = (defined('MODULE_PRODUCT_COMPARE_BADGE_COUNT_TEXT_COLOR')) ? MODULE_PRODUCT_COMPARE_BADGE_COUNT_TEXT_COLOR : '#ffffff';
    
    // Sanitize: Nur erlaubte Positionen
    $allowed_positions = array('bottom-right', 'bottom-left', 'top-right', 'top-left');
    if (!in_array($pc_badge_position, $allowed_positions)) {
        $pc_badge_position = 'bottom-right';
    }
    
    // Sanitize: HEX-Farben validieren
    $pc_badge_bg = preg_match('/^#[0-9a-fA-F]{3,6}$/', $pc_badge_bg) ? $pc_badge_bg : '#28a745';
    $pc_badge_text = preg_match('/^#[0-9a-fA-F]{3,6}$/', $pc_badge_text) ? $pc_badge_text : '#ffffff';
    $pc_badge_count_bg = preg_match('/^#[0-9a-fA-F]{3,6}$/', $pc_badge_count_bg) ? $pc_badge_count_bg : '#dc3545';
    $pc_badge_count_text = preg_match('/^#[0-9a-fA-F]{3,6}$/', $pc_badge_count_text) ? $pc_badge_count_text : '#ffffff';
    
    // Hover-Farbe berechnen (etwas dunkler als Hintergrund)
    $pc_badge_bg_hover = $pc_badge_bg; // Fallback
    if (preg_match('/^#([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/', $pc_badge_bg, $m)) {
        $r = max(0, hexdec($m[1]) - 20);
        $g = max(0, hexdec($m[2]) - 20);
        $b = max(0, hexdec($m[3]) - 20);
        $pc_badge_bg_hover = sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    // Inline-Styles für dynamische Farben ausgeben
    echo '<style>';
    echo '.product-compare-badge .compare-badge-link{background:' . $pc_badge_bg . ';color:' . $pc_badge_text . ';}';
    echo '.product-compare-badge .compare-badge-link:hover{background:' . $pc_badge_bg_hover . ';color:' . $pc_badge_text . ';}';
    echo '.product-compare-badge .compare-count{background:' . $pc_badge_count_bg . ';color:' . $pc_badge_count_text . ';}';
    echo '</style>';
    
    // Floating Compare Badge HTML mit Position-Klasse
    echo '<div id="product-compare-badge" class="product-compare-badge pc-pos-' . $pc_badge_position . ($pc_count > 0 ? ' active' : '') . '" title="' . (defined('PC_BADGE_TITLE') ? PC_BADGE_TITLE : 'Produktvergleich') . '">';
    echo '  <a href="' . $pc_compare_url . '" class="compare-badge-link">';
    echo '    <span class="fa fa-balance-scale"></span>';
    echo '    <span class="compare-count">' . $pc_count . '</span>';
    echo '  </a>';
    echo '</div>';
}
?>
