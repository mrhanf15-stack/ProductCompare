<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v1.7.7 - Header Head Hookpoint (Meta-Tags)
   
   Hookpoint: includes/extra/header/header_head/
   Datei:     ~/includes/header.php
   
   Setzt Meta-Titel und Meta-Description fuer die Produktvergleich-Seite.
   Die Werte werden im Admin unter System Module > Produktvergleich konfiguriert.
   
   In modified eCommerce setzt header.php die Variablen:
   - $meta_title (wird als <title> ausgegeben)
   - $meta_description (wird als <meta name="description"> ausgegeben)
   
   @author    Mr. Hanf / Manus AI
   @version   1.7.7
   @date      2026-03-13
   -----------------------------------------------------------------------------------------*/

if (defined('MODULE_PRODUCT_COMPARE_STATUS') && MODULE_PRODUCT_COMPARE_STATUS == 'true') {
    
    // Pruefen ob wir auf der Produktvergleich-Seite sind
    $pc_current_page = basename($_SERVER['SCRIPT_FILENAME']);
    
    if ($pc_current_page == 'product_compare.php') {
        
        // Meta-Titel aus der Konfiguration lesen
        if (defined('MODULE_PRODUCT_COMPARE_META_TITLE') && MODULE_PRODUCT_COMPARE_META_TITLE != '') {
            $meta_title = MODULE_PRODUCT_COMPARE_META_TITLE;
        }
        
        // Meta-Description aus der Konfiguration lesen
        if (defined('MODULE_PRODUCT_COMPARE_META_DESCRIPTION') && MODULE_PRODUCT_COMPARE_META_DESCRIPTION != '') {
            $meta_description = MODULE_PRODUCT_COMPARE_META_DESCRIPTION;
        }
        
        // Canonical URL setzen (verhindert Duplicate Content durch Session-Parameter)
        $pc_canonical = xtc_href_link('product_compare.php', '', 'SSL', false);
        // Variable fuer das Template bereitstellen
        if (isset($smarty) && is_object($smarty)) {
            $smarty->assign('pc_canonical', $pc_canonical);
        }
    }
}
?>
