# ProductCompare - Produktvergleich für modified eCommerce

**Version:** 1.0.0  
**Datum:** 12. März 2026  
**Autor:** Mr. Hanf / Manus AI  
**Kompatibilität:** modified eCommerce 2.x / 3.x mit bootstrap4 Template  
**Lizenz:** Proprietär

## Beschreibung

Das ProductCompare-Modul ermöglicht es Kunden, bis zu 6 Produkte anhand ihrer Artikelmerkmale (products_tags) direkt miteinander zu vergleichen. Das Modul ist vollständig als Autoinclude realisiert und erfordert keine Änderungen an bestehenden Core-Dateien.

Die Vergleichsfunktion nutzt das bestehende Seedfinder-Tag-System (THC, CBD, Genetik, Anbau, Blütezeit, Ertrag, Geschmack/Aroma, Wirkung) und zeigt alle Eigenschaften übersichtlich nebeneinander in einer Vergleichstabelle an.

## Funktionen

| Feature | Beschreibung |
|---------|-------------|
| Vergleich-Button | Automatisch in Seedfinder-Karten und auf Produktseiten |
| Session-basiert | Kein Login erforderlich |
| Max. 6 Produkte | Konfigurierbar im Admin |
| AJAX | Hinzufügen/Entfernen ohne Seitenreload |
| Floating Badge | Zeigt Anzahl der Vergleichsprodukte |
| Toast-Meldungen | Feedback bei Aktionen |
| Responsive | Optimiert für Desktop und Mobile |
| Vergleichsseite | Eigene Seite mit Vergleichstabelle |
| MutationObserver | Erkennt dynamisch geladene Seedfinder-Inhalte |

## Dateistruktur

```
shoproot/
├── product_compare.php                                    # Vergleichsseite
├── includes/
│   ├── extra/
│   │   ├── ajax/product_compare.php                       # AJAX-Endpoint
│   │   └── header/header_body/product_compare.php         # Floating Badge
│   └── modules/                                           # (reserviert)
├── lang/
│   ├── german/
│   │   ├── extra/product_compare.php                      # Deutsche Texte
│   │   └── modules/system/product_compare.php             # Admin-Texte DE
│   └── english/
│       ├── extra/product_compare.php                      # Englische Texte
│       └── modules/system/product_compare.php             # Admin-Texte EN
├── templates/bootstrap4/
│   ├── module/product_compare.html                        # Smarty-Template
│   ├── css/product_compare.css                            # Stylesheet
│   └── javascript/extra/product_compare.js.php            # JavaScript
└── admin/
    └── includes/modules/system/product_compare.php        # Admin-Modul
```

## Installation

1. Alle Dateien aus dem `shoproot/` Verzeichnis in das Shop-Root hochladen
2. Im Admin-Bereich navigieren zu: **Module > System Module**
3. **Produktvergleich** auswählen und **Installieren** klicken
4. Konfiguration anpassen (max. Produkte, Status)

## Konfiguration

| Einstellung | Beschreibung | Standard |
|-------------|-------------|----------|
| Modul aktivieren | Aktiviert/Deaktiviert das Modul | `true` |
| Maximale Anzahl Produkte | Max. Produkte im Vergleich (4-6 empfohlen) | `6` |
| Sortierreihenfolge | Sortierung im Admin | `0` |

## Verwendete Hookpoints

| Hookpoint | Datei | Funktion |
|-----------|-------|----------|
| `includes/extra/ajax/` | `product_compare.php` | AJAX: add/remove/clear/list |
| `includes/extra/header/header_body/` | `product_compare.php` | Floating Badge HTML |
| `templates/bootstrap4/javascript/extra/` | `product_compare.js.php` | JavaScript + CSS laden |

## Technische Details

Das Modul verwendet die modified eCommerce `products_tags` Tabellen, um die Artikelmerkmale für den Vergleich zu laden. Die Vergleichsliste wird in `$_SESSION['product_compare']` als Array von Produkt-IDs gespeichert. Das JavaScript injiziert automatisch Vergleich-Buttons in Seedfinder-Produktkarten (`.listingbox .card`) und auf Produktseiten (`form[name="cart_quantity"]`). Ein MutationObserver erkennt dynamisch nachgeladene Seedfinder-Inhalte und fügt die Buttons automatisch hinzu.

## Changelog

### v1.0.0 (12.03.2026)
- Initiale Version
- Session-basierter Produktvergleich
- AJAX-Endpoint für Hinzufügen/Entfernen
- Floating Badge mit Produktanzahl
- Vergleichsseite mit products_tags Tabelle
- Auto-Injection von Buttons in Seedfinder-Karten
- Responsive Design
- Deutsch und Englisch
