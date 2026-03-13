-- =========================================================================
-- ProductCompare v1.7.7 - SQL Update fuer bestehende Installationen
-- =========================================================================
-- 
-- WICHTIG: Dieses Script nur ausfuehren wenn das Modul bereits installiert
-- ist und die neuen Felder (Meta-Titel, Meta-Description, Sitemap) noch
-- nicht vorhanden sind.
--
-- Ausfuehrung: phpMyAdmin > SQL-Tab > Code einfuegen > Ausfuehren
-- =========================================================================

-- Meta-Titel Konfigurationsfeld
INSERT INTO configuration (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) 
VALUES ('MODULE_PRODUCT_COMPARE_META_TITLE', 'Produktvergleich - Cannabis Samen vergleichen', 6, 3, NOW())
ON DUPLICATE KEY UPDATE configuration_key = configuration_key;

-- Meta-Description Konfigurationsfeld
INSERT INTO configuration (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) 
VALUES ('MODULE_PRODUCT_COMPARE_META_DESCRIPTION', 'Vergleichen Sie Cannabis Samen direkt miteinander. Sorten, Eigenschaften und Preise auf einen Blick bei Mr. Hanf.', 6, 4, NOW())
ON DUPLICATE KEY UPDATE configuration_key = configuration_key;

-- Sitemap-Option Konfigurationsfeld
INSERT INTO configuration (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) 
VALUES ('MODULE_PRODUCT_COMPARE_SITEMAP', 'true', 6, 5, 'xtc_cfg_select_option(array(\'true\', \'false\'), ', NOW())
ON DUPLICATE KEY UPDATE configuration_key = configuration_key;
