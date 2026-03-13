# Sitemap-Integration fuer ProductCompare

## Eintrag in die Sitemap.xml

Fuege folgenden Eintrag in die `sitemap.xml` deines Shops ein (vor dem schliessenden `</urlset>` Tag):

```xml
<url>
  <loc>https://mr-hanf.de/product_compare.php</loc>
  <lastmod>2026-03-13</lastmod>
  <changefreq>weekly</changefreq>
  <priority>0.5</priority>
</url>
```

### Wo befindet sich die Sitemap?

Die Sitemap wird in modified eCommerce ueber das Export-Modul generiert:
- **Admin > Module > Export-Module > Google Sitemap**
- Die generierte Datei liegt im Shop-Root: `sitemap.xml`

### Wichtig

Da modified eCommerce keinen Autoinclude-Hookpoint fuer die Sitemap bietet, muss der Eintrag **nach jeder Neugenerierung** der Sitemap manuell hinzugefuegt werden. Alternativ kann der Eintrag auch direkt in die Sitemap-Generierungsdatei eingefuegt werden:

**Datei:** `admin/includes/modules/export/googlebase_sitemap.php`

Suche nach der Stelle wo `</urlset>` geschrieben wird und fuege davor ein:

```php
// ProductCompare Sitemap-Eintrag
if (defined('MODULE_PRODUCT_COMPARE_STATUS') && MODULE_PRODUCT_COMPARE_STATUS == 'true'
    && defined('MODULE_PRODUCT_COMPARE_SITEMAP') && MODULE_PRODUCT_COMPARE_SITEMAP == 'true') {
    $sitemap_entry  = '<url>' . "\n";
    $sitemap_entry .= '  <loc>' . xtc_href_link('product_compare.php', '', 'SSL', false) . '</loc>' . "\n";
    $sitemap_entry .= '  <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
    $sitemap_entry .= '  <changefreq>weekly</changefreq>' . "\n";
    $sitemap_entry .= '  <priority>0.5</priority>' . "\n";
    $sitemap_entry .= '</url>' . "\n";
    fwrite($fp, $sitemap_entry);
}
```
