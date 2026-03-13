<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v1.7.9 - Admin System Module
   
   Pfad: admin/includes/modules/system/product_compare.php
   
   Konfiguration des Produktvergleich-Moduls im Admin-Bereich.
   Admin > Module > System Module > Produktvergleich
   
   v1.7.9: Sitemap-Option entfernt (wird extern verwaltet)
   v1.7.8: Auto-Update - fehlende Konfigurationsfelder werden automatisch angelegt
   v1.7.7: Meta-Titel, Meta-Description hinzugefuegt
   
   @author    Mr. Hanf / Manus AI
   @version   1.7.9
   @date      2026-03-13
   -----------------------------------------------------------------------------------------*/

defined('_VALID_XTC') or die('Direct Access to this location is not allowed.');

class product_compare {
    var $code, $title, $description, $sort_order, $enabled, $keys;
    
    function __construct() {
        $this->code = 'product_compare';
        $this->title = (defined('MODULE_PRODUCT_COMPARE_TEXT_TITLE')) ? MODULE_PRODUCT_COMPARE_TEXT_TITLE : 'Produktvergleich';
        $this->description = (defined('MODULE_PRODUCT_COMPARE_TEXT_DESCRIPTION')) ? MODULE_PRODUCT_COMPARE_TEXT_DESCRIPTION : 'Ermöglicht Kunden, Produkte anhand ihrer Artikelmerkmale (products_tags) direkt miteinander zu vergleichen.';
        $this->sort_order = (defined('MODULE_PRODUCT_COMPARE_SORT_ORDER')) ? MODULE_PRODUCT_COMPARE_SORT_ORDER : 0;
        $this->enabled = (defined('MODULE_PRODUCT_COMPARE_STATUS') && MODULE_PRODUCT_COMPARE_STATUS == 'true') ? true : false;
        
        // Auto-Update: Fehlende Konfigurationsfelder automatisch anlegen
        if ($this->enabled || (defined('MODULE_PRODUCT_COMPARE_STATUS'))) {
            $this->_auto_update();
        }
    }
    
    /**
     * Prueft ob alle Konfigurationsfelder vorhanden sind und legt fehlende automatisch an.
     * So muss bei Updates kein manuelles SQL ausgefuehrt werden.
     */
    function _auto_update() {
        $required_fields = array(
            'MODULE_PRODUCT_COMPARE_META_TITLE' => array(
                'value' => 'Produktvergleich - Cannabis Samen vergleichen',
                'sort'  => 3,
                'func'  => ''
            ),
            'MODULE_PRODUCT_COMPARE_META_DESCRIPTION' => array(
                'value' => 'Vergleichen Sie Cannabis Samen direkt miteinander. Sorten, Eigenschaften und Preise auf einen Blick bei Mr. Hanf.',
                'sort'  => 4,
                'func'  => ''
            )
        );
        
        foreach ($required_fields as $key => $config) {
            $check_query = xtc_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = '" . xtc_db_input($key) . "'");
            if (xtc_db_num_rows($check_query) == 0) {
                $set_function = ($config['func'] != '') ? "'" . xtc_db_input($config['func']) . "'" : "NULL";
                xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('" . xtc_db_input($key) . "', '" . xtc_db_input($config['value']) . "', 6, " . (int)$config['sort'] . ", " . $set_function . ", now())");
            }
        }
        
        // Aufraumen: Altes Sitemap-Feld entfernen falls vorhanden
        $old_query = xtc_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PRODUCT_COMPARE_SITEMAP'");
        if (xtc_db_num_rows($old_query) > 0) {
            xtc_db_query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PRODUCT_COMPARE_SITEMAP'");
        }
    }
    
    function process() {
        // Wird bei jedem Seitenaufruf ausgefuehrt
    }
    
    function display() {
        return array(
            'text' => '<br>' . xtc_button(BUTTON_SAVE) . xtc_button_link(xtc_href_link(FILENAME_MODULE_EXPORT, 'set=system'), BUTTON_CANCEL)
        );
    }
    
    function check() {
        if (!isset($this->_check)) {
            $check_query = xtc_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PRODUCT_COMPARE_STATUS'");
            $this->_check = xtc_db_num_rows($check_query);
        }
        return $this->_check;
    }
    
    function install() {
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('MODULE_PRODUCT_COMPARE_STATUS', 'true', 6, 1, 'xtc_cfg_select_option(array(\'true\', \'false\'), ', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_PRODUCT_COMPARE_MAX_PRODUCTS', '6', 6, 2, now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_PRODUCT_COMPARE_META_TITLE', 'Produktvergleich - Cannabis Samen vergleichen', 6, 3, now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_PRODUCT_COMPARE_META_DESCRIPTION', 'Vergleichen Sie Cannabis Samen direkt miteinander. Sorten, Eigenschaften und Preise auf einen Blick bei Mr. Hanf.', 6, 4, now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_PRODUCT_COMPARE_SORT_ORDER', '0', 6, 5, now())");
    }
    
    function remove() {
        xtc_db_query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN ('" . implode("', '", $this->keys()) . "')");
    }
    
    function keys() {
        return array(
            'MODULE_PRODUCT_COMPARE_STATUS',
            'MODULE_PRODUCT_COMPARE_MAX_PRODUCTS',
            'MODULE_PRODUCT_COMPARE_META_TITLE',
            'MODULE_PRODUCT_COMPARE_META_DESCRIPTION',
            'MODULE_PRODUCT_COMPARE_SORT_ORDER'
        );
    }
}
?>
