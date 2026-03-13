# Vergleich-Button in der Produktlisting einfuegen

## Waagen-Icon Button Code

Der folgende Code-Snippet fuegt einen Vergleich-Button (Waagen-Icon) in die Produktlisting ein.
Er hat das **gleiche Design und die gleiche Groesse** wie die bestehenden Buttons (Warenkorb, Merkzettel).

### Code-Snippet

```smarty
{if defined('MODULE_PRODUCT_COMPARE_STATUS') && $smarty.const.MODULE_PRODUCT_COMPARE_STATUS == 'true'}<button class="btn btn-compare btn-outline-secondary btn-sm" data-product-id="{$module_data.PRODUCTS_ID}" title="Vergleichen"><span class="fa fa-balance-scale"></span></button>&nbsp;&nbsp;{/if}
```

---

## Einbau in `product_listing.html`

Das Snippet muss an **3 Stellen** eingefuegt werden:

### 1. Grid-Ansicht (Box) - Zeile 62

**Suche:**
```smarty
{/if}{$module_data.PRODUCTS_BUTTON_DETAILS}</div>
```

**Ersetze mit:**
```smarty
{/if}{if defined('MODULE_PRODUCT_COMPARE_STATUS') && $smarty.const.MODULE_PRODUCT_COMPARE_STATUS == 'true'}<button class="btn btn-compare btn-outline-secondary btn-sm" data-product-id="{$module_data.PRODUCTS_ID}" title="Vergleichen"><span class="fa fa-balance-scale"></span></button>&nbsp;&nbsp;{/if}{$module_data.PRODUCTS_BUTTON_DETAILS}</div>
```

### 2. Row-Ansicht mit Optionen (MODULE_PRODUCT_QUANTITYADD) - Zeile 175

**Suche:**
```smarty
{if !empty($module_data.PRODUCTS_LINK_WISHLIST_NOW)} {$smarty.const.TEXT_TO_WISHLIST|bs4button:'button_wishlist_now':'name="wishlist"':true}&nbsp;&nbsp;{/if}{$module_data.PRODUCTS_BUTTON_DETAILS}
```

**Ersetze mit:**
```smarty
{if !empty($module_data.PRODUCTS_LINK_WISHLIST_NOW)} {$smarty.const.TEXT_TO_WISHLIST|bs4button:'button_wishlist_now':'name="wishlist"':true}&nbsp;&nbsp;{/if}{if defined('MODULE_PRODUCT_COMPARE_STATUS') && $smarty.const.MODULE_PRODUCT_COMPARE_STATUS == 'true'}<button class="btn btn-compare btn-outline-secondary btn-sm" data-product-id="{$module_data.PRODUCTS_ID}" title="Vergleichen"><span class="fa fa-balance-scale"></span></button>&nbsp;&nbsp;{/if}{$module_data.PRODUCTS_BUTTON_DETAILS}
```

### 3. Row-Ansicht ohne Optionen - Zeile 182

**Suche:**
```smarty
{/if}{if $module_data.PRODUCTS_LINK_WISHLIST_NOW}<a href="{$module_data.PRODUCTS_LINK_WISHLIST_NOW}" rel="nofollow">{$dummy|bs4button:'button_wishlist_now'}</a>&nbsp;&nbsp;{/if}{$module_data.PRODUCTS_BUTTON_DETAILS}</div>
```

**Ersetze mit:**
```smarty
{/if}{if $module_data.PRODUCTS_LINK_WISHLIST_NOW}<a href="{$module_data.PRODUCTS_LINK_WISHLIST_NOW}" rel="nofollow">{$dummy|bs4button:'button_wishlist_now'}</a>&nbsp;&nbsp;{/if}{if defined('MODULE_PRODUCT_COMPARE_STATUS') && $smarty.const.MODULE_PRODUCT_COMPARE_STATUS == 'true'}<button class="btn btn-compare btn-outline-secondary btn-sm" data-product-id="{$module_data.PRODUCTS_ID}" title="Vergleichen"><span class="fa fa-balance-scale"></span></button>&nbsp;&nbsp;{/if}{$module_data.PRODUCTS_BUTTON_DETAILS}</div>
```

---

## Hinweise

- Der Button nutzt die Klasse `btn-compare` die bereits im `product_compare.css` definiert ist
- Das CSS wird automatisch auf jeder Seite geladen (ueber den header_body Hookpoint)
- Das JavaScript (`product_compare.js.php`) erkennt den Button automatisch ueber die Klasse `btn-compare` und das Attribut `data-product-id`
- Wenn das Modul deaktiviert wird, verschwindet der Button automatisch (if-Abfrage)
- Der Button wechselt automatisch zwischen "Vergleichen" (Waage) und "Im Vergleich" (Haken) per JavaScript
