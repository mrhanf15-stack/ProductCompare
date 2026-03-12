# ProductCompare v1.2.0 - Template-Änderungen

## Übersicht

In v1.2.0 werden die Vergleichen-Buttons **direkt in den Smarty-Templates** platziert statt per JavaScript-Injection. Das ist stabiler und funktioniert sofort beim Seitenaufbau.

Es gibt **3 Stellen** wo Änderungen nötig sind:

---

## 1. Produktseite: Kleiner Merkzettel-Button ersetzen

**Datei:** `templates/bootstrap4/module/product_info/product_info_tabs_v1.html`

**Suche** (ca. Zeile 219) den kleinen Merkzettel-Button in der unteren Button-Leiste:

```smarty
{if $ADD_CART_BUTTON_WISHLIST}<div class="col-sm-6 mb-2">{$ADD_CART_BUTTON_WISHLIST|replace:"btn-sm":"btn-xs"|replace:"-outline":""}</div><div class="clearfix"></div>{/if}
```

**Ersetze** durch den Vergleichen-Button:

```smarty
{if $smarty.const.MODULE_PRODUCT_COMPARE_STATUS == 'true'}<div class="col-sm-6 mb-2"><button type="button" class="btn btn-compare btn-info btn-xs btn-block" data-product-id="{$PRODUCTS_ID}" onclick="event.preventDefault(); window.ProductCompare && window.ProductCompare.toggle('{$PRODUCTS_ID}', this);"><span class="fa fa-balance-scale mr-1"></span><span>Vergleichen</span></button></div><div class="clearfix"></div>{/if}
```

**Hinweis:** Falls der Merkzettel-Button auf dem Server nicht auskommentiert ist (wie im Screenshot zu sehen), ersetze die aktive Zeile. Der große Merkzettel-Button oben (Zeile 195-197) bleibt unverändert.

---

## 2. Seedfinder-Karten: Vergleichen-Button hinzufügen

**Datei:** `templates/bootstrap4/module/seedfinder_product_cards.html` (auf dem Server)

**Suche** den Details-Button-Bereich. Auf dem Server sieht er so aus:

```html
<div><a href="{...}" class="btn btn-info btn-sm btn-block"><span class="fa fa-eye mr-1"></span>Details ansehen</a></div>
```

**Füge DANACH** den Vergleichen-Button ein:

```smarty
{if $smarty.const.MODULE_PRODUCT_COMPARE_STATUS == 'true'}
<div class="mt-2"><button type="button" class="btn btn-compare btn-outline-secondary btn-sm btn-block" data-sku="{$product.PRODUCTS_MODEL}" onclick="event.preventDefault(); window.ProductCompare && window.ProductCompare.toggle('{$product.PRODUCTS_MODEL}', this);"><span class="fa fa-balance-scale mr-1"></span><span>Vergleichen</span></button></div>
{/if}
```

**Hinweis:** Da die Seedfinder-Karten keine `products_id` direkt haben, nutzen wir `data-sku` mit der Artikelnummer (`PRODUCTS_MODEL`). Das JavaScript löst die SKU per AJAX zur `products_id` auf.

Falls `PRODUCTS_MODEL` nicht verfügbar ist im Seedfinder-Template, nutze stattdessen die SKU aus dem meta-Tag. In dem Fall muss die Variable im PHP-Code des Seedfinder-Moduls bereitgestellt werden.

---

## 3. Standard-Produktlisten: Vergleichen-Button hinzufügen (optional)

**Datei:** `templates/bootstrap4/module/includes/product_info_include.html`

**Suche** (Zeile 48) den Bereich mit dem Merkzettel-Button in der Boxansicht:

```smarty
{if $module_data.PRODUCTS_LINK_WISHLIST_NOW}<a href="{$module_data.PRODUCTS_LINK_WISHLIST_NOW}" aria-label="{$smarty.const.TEXT_TO_WISHLIST}">{$dummy|bs4button:'button_wishlist_now'}</a>&nbsp;&nbsp;{/if}
```

**Füge DANACH** den Vergleichen-Button ein:

```smarty
{if $smarty.const.MODULE_PRODUCT_COMPARE_STATUS == 'true'}<button type="button" class="btn btn-compare btn-outline-secondary btn-xs" data-product-id="{$module_data.PRODUCTS_ID}" onclick="event.preventDefault(); window.ProductCompare && window.ProductCompare.toggle('{$module_data.PRODUCTS_ID}', this);"><span class="fa fa-balance-scale"></span></button>&nbsp;&nbsp;{/if}
```

---

## Zusammenfassung der Änderungen

| Datei | Änderung | Smarty-Variable |
|-------|----------|-----------------|
| `product_info_tabs_v1.html` | Kleiner Merkzettel → Vergleichen | `{$PRODUCTS_ID}` |
| `seedfinder_product_cards.html` | Button nach Details-Button | `{$product.PRODUCTS_MODEL}` |
| `product_info_include.html` | Button neben Merkzettel (optional) | `{$module_data.PRODUCTS_ID}` |

Alle Buttons prüfen `MODULE_PRODUCT_COMPARE_STATUS == 'true'` und werden nur angezeigt wenn das Modul aktiv ist.
