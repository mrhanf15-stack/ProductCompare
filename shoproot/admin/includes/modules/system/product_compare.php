<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v1.7.7 - Admin System Module
   
   Pfad: admin/includes/modules/system/product_compare.php
   
   Konfiguration des Produktvergleich-Moduls im Admin-Bereich.
   Admin > Module > System Module > Produktvergleich
   
   v1.7.7: Meta-Titel, Meta-Description und Sitemap-URL hinzugefuegt
   
   @author    Mr. Hanf / Manus AI
   @version   1.7.7
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
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('MODULE_PRODUCT_COMPARE_SITEMAP', 'true', 6, 5, 'xtc_cfg_select_option(array(\'true\', \'false\'), ', now())");
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_PRODUCT_COMPARE_SORT_ORDER', '0', 6, 6, now())");
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
            'MODULE_PRODUCT_COMPARE_SITEMAP',
            'MODULE_PRODUCT_COMPARE_SORT_ORDER'
        );
    }
}
?>
