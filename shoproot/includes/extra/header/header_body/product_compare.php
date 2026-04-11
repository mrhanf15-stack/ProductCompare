<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v2.2.0 - Header Body Hookpoint

   Hookpoint: includes/extra/header/header_body/

   Lädt JavaScript und CSS für den Produktvergleich auf allen Seiten.
   Zeigt den Floating-Badge mit der Anzahl der Vergleichsprodukte.

   v2.2.0: Inline-Styles entfernt – Farben kommen jetzt aus dem
           MRH 2026 Konfigurator (colors.json → CSS-Variablen in :root).
           Die Admin-Konstanten MODULE_PRODUCT_COMPARE_BADGE_*_COLOR
           werden nur noch als Fallback verwendet wenn keine CSS-Variablen
           gesetzt sind (product_compare.css enthält var() mit Fallbacks).
           → Kein <style>-Block mehr nötig!
   v2.1.0: BS5.3 + FA6 Migration
   v1.9.0: Cookie-basierte Persistenz - Vergleichsliste überlebt Logout/Login
   v1.8.1: Badge-Position und Farben aus Admin-Konfiguration lesen
   v1.0.1: DIR_FS_LANGUAGES → DIR_WS_LANGUAGES (korrekte Konstante in modified)

   @author    Mr. Hanf / Manus AI
   @version   2.2.0
   @date      2026-04-11
   -----------------------------------------------------------------------------------------*/

if (defined('MODULE_PRODUCT_COMPARE_STATUS') && MODULE_PRODUCT_COMPARE_STATUS == 'true') {

    // Session initialisieren
    if (!isset($_SESSION['product_compare'])) {
        $_SESSION['product_compare'] = array();
    }

    // === Cookie-Restore: Session leer aber Cookie vorhanden? ===
    // Dies tritt auf nach Logout (Session zerstört) → Login (neue Session)
    if (empty($_SESSION['product_compare']) && isset($_COOKIE['pc_compare_ids']) && $_COOKIE['pc_compare_ids'] !== '') {
        $pc_cookie_raw = $_COOKIE['pc_compare_ids'];
        $pc_cookie_ids = array_map('intval', explode(',', $pc_cookie_raw));
        $pc_cookie_ids = array_filter($pc_cookie_ids, function($id) { return $id > 0; });
        $pc_cookie_ids = array_unique($pc_cookie_ids);

        if (!empty($pc_cookie_ids)) {
            // Validieren: Nur existierende, aktive Produkte übernehmen
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

    $pc_count = count($_SESSION['product_compare']);
    $pc_max = (defined('MODULE_PRODUCT_COMPARE_MAX_PRODUCTS')) ? (int)MODULE_PRODUCT_COMPARE_MAX_PRODUCTS : 6;
    $pc_compare_url = xtc_href_link('product_compare.php');

    // Sprachdatei laden - DIR_WS_LANGUAGES ist die korrekte Konstante (definiert in includes/paths.php)
    $pc_lang_file = DIR_WS_LANGUAGES . $_SESSION['language'] . '/extra/product_compare.php';
    if (file_exists($pc_lang_file)) {
        require_once($pc_lang_file);
    }

    // Badge-Position aus Admin lesen (Farben kommen jetzt aus dem Konfigurator via CSS-Variablen)
    $pc_badge_position = (defined('MODULE_PRODUCT_COMPARE_BADGE_POSITION')) ? MODULE_PRODUCT_COMPARE_BADGE_POSITION : 'bottom-right';

    // Sanitize: Nur erlaubte Positionen
    $allowed_positions = array('bottom-right', 'bottom-left', 'top-right', 'top-left');
    if (!in_array($pc_badge_position, $allowed_positions)) {
        $pc_badge_position = 'bottom-right';
    }

    // v2.2.0: KEIN Inline-<style> mehr!
    // Farben werden über CSS-Variablen gesteuert:
    //   --tpl-compare-float-bg, --tpl-compare-float-text, --tpl-compare-float-hover-bg
    //   --tpl-compare-float-count-bg, --tpl-compare-float-count-text
    // Diese werden in general.css.php aus colors.json als :root{} ausgegeben.
    // product_compare.css enthält var() mit sinnvollen Fallbacks.

    // Floating Compare Badge HTML mit Position-Klasse
    echo '<div id="product-compare-badge" class="product-compare-badge pc-pos-' . $pc_badge_position . ($pc_count > 0 ? ' active' : '') . '" title="' . (defined('PC_BADGE_TITLE') ? PC_BADGE_TITLE : 'Produktvergleich') . '">';
    echo '  <a href="' . $pc_compare_url . '" class="compare-badge-link">';
    echo '    <span class="fa-solid fa-scale-balanced"></span>';
    echo '    <span class="compare-count">' . $pc_count . '</span>';
    echo '  </a>';
    echo '</div>';
}
?>
