<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v1.9.1 - JavaScript (als .js.php für Smarty-Variablen)

   Hookpoint: templates/bootstrap4/javascript/extra/
   Wird automatisch auf jeder Seite geladen.

   v1.9.4: BUGFIX - Doppelter Confirm-Dialog behoben
           - handleClearClick: confirm() entfernt (onclick im Template übernimmt)
           - Verhindert doppelte Abfrage beim Leeren
   v1.9.3: BUGFIX - Clear-Button Selector fix + clearAll() global
           - handleClearClick: Selector erweitert für HTML-encoded URLs und CSS-Klasse
           - ProductCompare.clearAll() als globale Funktion für onclick-Aufrufe
           - Funktioniert mit und ohne onclick im Template
   v1.9.2: BUGFIX - FPC-sichere Initialisierung
           - currentProducts wird IMMER per AJAX geladen (nicht aus gecachtem PHP)
           - Cookie-Restore nur wenn Server-Session leer + Cookie vorhanden
           - Verhindert dass FPC gecachte Produkt-IDs das Cookie wieder setzen
   v1.9.1: BUGFIX - "Liste leeren" per AJAX statt Page-Link
           - Clear löscht Cookie clientseitig (pcClearCookie)
           - Clear löscht Cookie serverseitig (AJAX sub_action=clear)
           - Badge wird sofort auf 0 aktualisiert
           - Seite wird nach AJAX-Clear neu geladen
   v1.9.0: Cookie-basierte Persistenz - Vergleichsliste überlebt Logout/Login
           - pcSaveCookie(): Speichert IDs als Cookie (30 Tage)
           - pcLoadCookie(): Liest IDs aus Cookie
           - Sync bei jedem add/remove/clear
           - Restore beim Seitenaufruf wenn Session leer aber Cookie vorhanden
   v1.2.0: Neuer Ansatz - Buttons direkt in Smarty-Templates
   v1.2.2: Bugfixes

   @author    Mr. Hanf / Manus AI
   @version   1.9.4
   @date      2026-03-20
   -----------------------------------------------------------------------------------------*/

if (defined('MODULE_PRODUCT_COMPARE_STATUS') && MODULE_PRODUCT_COMPARE_STATUS == 'true'):
?>
<link rel="stylesheet" href="<?php echo (defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/'); ?>templates/bootstrap4/css/product_compare.css">
<script>
(function() {
    'use strict';

    // === Cookie-Helper Funktionen ===
    function pcSaveCookie(ids) {
        var value = (ids || []).map(Number).filter(function(id){ return id > 0; }).join(',');
        var expires = new Date();
        expires.setTime(expires.getTime() + (30 * 24 * 60 * 60 * 1000)); // 30 Tage
        document.cookie = 'pc_compare_ids=' + value + ';expires=' + expires.toUTCString() + ';path=/;SameSite=Lax';
    }

    function pcLoadCookie() {
        var match = document.cookie.match(/(?:^|;\s*)pc_compare_ids=([^;]*)/);
        if (match && match[1] && match[1] !== '') {
            return match[1].split(',').map(Number).filter(function(id){ return id > 0; });
        }
        return [];
    }

    function pcClearCookie() {
        document.cookie = 'pc_compare_ids=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;SameSite=Lax';
    }

    // === Konfiguration ===
    var PC = {
        ajaxUrl: '<?php echo (defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/'); ?>ajax.php?ext=product_compare',
        compareUrl: '<?php echo xtc_href_link("product_compare.php"); ?>',
        maxProducts: <?php echo (defined('MODULE_PRODUCT_COMPARE_MAX_PRODUCTS') ? (int)MODULE_PRODUCT_COMPARE_MAX_PRODUCTS : 6); ?>,
        currentProducts: [], // v1.9.2: IMMER leer initialisieren (FPC-sicher), wird per AJAX geladen

        // Texte
        text: {
            add: '<?php echo addslashes(defined("PC_BUTTON_ADD") ? PC_BUTTON_ADD : "Vergleichen"); ?>',
            added: '<?php echo addslashes(defined("PC_BUTTON_ADDED") ? PC_BUTTON_ADDED : "Im Vergleich"); ?>',
            compareNow: '<?php echo addslashes(defined("PC_BUTTON_COMPARE_NOW") ? PC_BUTTON_COMPARE_NOW : "Jetzt vergleichen"); ?>',
            msgAdded: '<?php echo addslashes(defined("PC_MSG_ADDED") ? PC_MSG_ADDED : "Produkt zum Vergleich hinzugefügt"); ?>',
            msgRemoved: '<?php echo addslashes(defined("PC_MSG_REMOVED") ? PC_MSG_REMOVED : "Produkt aus dem Vergleich entfernt"); ?>',
            msgAlready: '<?php echo addslashes(defined("PC_MSG_ALREADY") ? PC_MSG_ALREADY : "Produkt ist bereits im Vergleich"); ?>',
            msgMaxReached: '<?php echo addslashes(defined("PC_MSG_MAX_REACHED") ? str_replace("%s", (defined("MODULE_PRODUCT_COMPARE_MAX_PRODUCTS") ? MODULE_PRODUCT_COMPARE_MAX_PRODUCTS : "6"), PC_MSG_MAX_REACHED) : "Maximale Anzahl erreicht"); ?>',
            msgCleared: '<?php echo addslashes(defined("PC_MSG_CLEARED") ? PC_MSG_CLEARED : "Vergleichsliste geleert"); ?>'
        },

        // Cache: SKU → products_id Mapping
        skuMap: {}
    };

    // === v1.9.2: FPC-sichere Initialisierung per AJAX ===
    // currentProducts ist immer leer (FPC-sicher).
    // Beim Seitenaufruf wird die echte Liste per AJAX vom Server geholt.
    // Wenn Server-Session leer aber Cookie vorhanden → Cookie-Restore.
    var initDone = false;
    function initFromServer() {
        ajaxCompare('list', null, function(data) {
            if (data.success) {
                var serverProducts = (data.products || []).map(Number);
                var serverCount = data.count || 0;

                if (serverCount > 0) {
                    // Server hat Produkte → übernehmen + Cookie synchronisieren
                    PC.currentProducts = serverProducts;
                    pcSaveCookie(PC.currentProducts);
                    updateBadge(serverCount);
                    updateAllButtons();
                    initDone = true;
                } else {
                    // Server-Session leer → Cookie prüfen für Restore
                    var cookieIds = pcLoadCookie();
                    if (cookieIds.length > 0) {
                        // Cookie hat Daten → per AJAX in Session wiederherstellen
                        var restoreCount = 0;
                        var totalToRestore = cookieIds.length;

                        // Sofort lokal setzen für schnelle UI-Reaktion
                        PC.currentProducts = cookieIds;
                        updateBadge(cookieIds.length);
                        updateAllButtons();

                        cookieIds.forEach(function(pid) {
                            var url = PC.ajaxUrl + '&sub_action=add&products_id=' + pid;
                            var xhr = new XMLHttpRequest();
                            xhr.open('GET', url, true);
                            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                            xhr.onreadystatechange = function() {
                                if (xhr.readyState === 4) {
                                    restoreCount++;
                                    if (restoreCount >= totalToRestore) {
                                        ajaxCompare('list', null, function(data2) {
                                            if (data2.success) {
                                                PC.currentProducts = (data2.products || []).map(Number);
                                                pcSaveCookie(PC.currentProducts);
                                                updateBadge(PC.currentProducts.length);
                                                updateAllButtons();
                                            }
                                        });
                                    }
                                }
                            };
                            xhr.send();
                        });
                    } else {
                        // Beides leer → nichts zu tun
                        PC.currentProducts = [];
                        updateBadge(0);
                        updateAllButtons();
                    }
                    initDone = true;
                }
            }
        });
    }

    // === Toast-Benachrichtigung ===
    var toastEl = null;
    var toastTimeout = null;

    function createToast() {
        if (toastEl) return;
        toastEl = document.createElement('div');
        toastEl.className = 'compare-toast';
        document.body.appendChild(toastEl);
    }

    function showToast(message, type) {
        createToast();
        toastEl.textContent = message;
        toastEl.className = 'compare-toast ' + (type || 'info');
        void toastEl.offsetWidth;
        toastEl.classList.add('show');

        if (toastTimeout) clearTimeout(toastTimeout);
        toastTimeout = setTimeout(function() {
            toastEl.classList.remove('show');
        }, 3000);
    }

    // === Badge aktualisieren ===
    function updateBadge(count) {
        var badge = document.getElementById('product-compare-badge');
        if (!badge) return;

        var countEl = badge.querySelector('.compare-count');
        if (countEl) countEl.textContent = count;

        if (count > 0) {
            badge.classList.add('active');
        } else {
            badge.classList.remove('active');
        }
    }

    // === AJAX-Aufruf ===
    function ajaxCompare(subAction, productId, callback) {
        var url = PC.ajaxUrl + '&sub_action=' + subAction;
        if (productId) url += '&products_id=' + productId;

        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (callback) callback(data);
                } catch(e) {
                    console.error('ProductCompare: JSON parse error', e);
                }
            }
        };
        xhr.send();
    }

    // === SKU → products_id per AJAX auflösen ===
    function resolveProductId(sku, callback) {
        if (PC.skuMap[sku]) {
            callback(PC.skuMap[sku]);
            return;
        }

        var url = PC.ajaxUrl + '&sub_action=resolve_sku&sku=' + encodeURIComponent(sku);
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success && data.products_id) {
                        PC.skuMap[sku] = data.products_id;
                        callback(data.products_id);
                    }
                } catch(e) {
                    console.error('ProductCompare: SKU resolve error', e);
                }
            }
        };
        xhr.send();
    }

    // === Produkt hinzufügen/entfernen (Toggle) ===
    function toggleCompare(productId, button) {
        // Wenn productId eine SKU ist (enthält Buchstaben), erst auflösen
        if (isNaN(productId)) {
            resolveProductId(productId, function(resolvedId) {
                doToggle(resolvedId, button);
            });
        } else {
            doToggle(productId, button);
        }
    }

    function doToggle(productId, button) {
        productId = parseInt(productId);
        var isInList = PC.currentProducts.indexOf(productId) !== -1;

        if (isInList) {
            ajaxCompare('remove', productId, function(data) {
                if (data.success) {
                    PC.currentProducts = data.products.map(Number);
                    pcSaveCookie(PC.currentProducts); // Cookie sync
                    updateBadge(data.count);
                    updateAllButtons();
                    showToast(PC.text.msgRemoved, 'info');
                }
            });
        } else {
            if (PC.currentProducts.length >= PC.maxProducts) {
                showToast(PC.text.msgMaxReached, 'error');
                return;
            }

            ajaxCompare('add', productId, function(data) {
                if (data.success) {
                    PC.currentProducts = data.products.map(Number);
                    pcSaveCookie(PC.currentProducts); // Cookie sync
                    updateBadge(data.count);
                    updateAllButtons();
                    showToast(PC.text.msgAdded, 'success');
                } else if (data.message === 'already_in_list') {
                    showToast(PC.text.msgAlready, 'info');
                } else if (data.message === 'max_reached') {
                    showToast(PC.text.msgMaxReached, 'error');
                }
            });
        }
    }

    // === v1.9.1: Liste leeren per AJAX ===
    function clearCompare(reloadPage) {
        // 1. Cookie sofort clientseitig löschen
        pcClearCookie();

        // 2. Lokale Liste leeren
        PC.currentProducts = [];

        // 3. Badge sofort aktualisieren
        updateBadge(0);
        updateAllButtons();

        // 4. Server-Session per AJAX leeren (löscht auch Cookie serverseitig)
        ajaxCompare('clear', null, function(data) {
            if (data.success) {
                showToast(PC.text.msgCleared, 'info');
                // 5. Seite neu laden wenn gewünscht (z.B. auf der Vergleichsseite)
                if (reloadPage) {
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                }
            }
        });
    }

    // === Alle Buttons aktualisieren ===
    function updateAllButtons() {
        var buttons = document.querySelectorAll('.btn-compare[data-product-id]');
        buttons.forEach(function(btn) {
            var pid = parseInt(btn.getAttribute('data-product-id'));
            var isInList = PC.currentProducts.indexOf(pid) !== -1;

            if (isInList) {
                btn.classList.add('active');
                btn.innerHTML = '<span class="fa fa-check mr-1"></span>' + PC.text.added;
            } else {
                btn.classList.remove('active');
                btn.innerHTML = '<span class="fa fa-balance-scale mr-1"></span>' + PC.text.add;
            }
        });
    }

    // === Seedfinder-Karten: SKU-basierte Buttons initialisieren ===
    function initSeedfinderButtons() {
        // Seedfinder-Karten haben data-sku statt data-product-id
        var skuButtons = document.querySelectorAll('.btn-compare[data-sku]:not([data-product-id])');

        skuButtons.forEach(function(btn) {
            var sku = btn.getAttribute('data-sku');
            if (!sku) return;

            // SKU → products_id auflösen
            resolveProductId(sku, function(productId) {
                btn.setAttribute('data-product-id', productId);

                // Prüfe ob das Produkt bereits im Vergleich ist
                var isInList = PC.currentProducts.indexOf(parseInt(productId)) !== -1;
                if (isInList) {
                    btn.classList.add('active');
                    btn.innerHTML = '<span class="fa fa-check mr-1"></span>' + PC.text.added;
                }
            });
        });
    }

    // === Button-Click-Handler (Event Delegation) ===
    function handleCompareClick(e) {
        var btn = e.target.closest('.btn-compare');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        var productId = btn.getAttribute('data-product-id');
        var sku = btn.getAttribute('data-sku');

        if (productId) {
            toggleCompare(productId, btn);
        } else if (sku) {
            toggleCompare(sku, btn);
        }
    }

    // === v1.9.4: Clear-Button Click-Handler (Event Delegation) ===
    // Fallback falls onclick im Template nicht vorhanden ist.
    // Confirm-Dialog wird NICHT hier gezeigt (onclick im Template übernimmt das).
    function handleClearClick(e) {
        var link = e.target.closest('a[href*="action=clear"]');
        if (!link) link = e.target.closest('a.btn-outline-danger[href*="product_compare"]');
        if (!link) link = e.target.closest('a.btn-outline-danger[href*="vergleich"]');
        if (!link) return;

        // Nur abfangen wenn KEIN onclick im Template vorhanden ist
        // (onclick ruft bereits ProductCompare.clearAll() auf)
        if (link.hasAttribute('onclick')) return;

        e.preventDefault();
        e.stopPropagation();

        if (confirm('Vergleichsliste wirklich leeren?')) {
            clearCompare(true);
        }
    }

    // === Initialisierung ===
    function init() {
        // v1.9.2: Badge startet bei 0, wird per AJAX aktualisiert
        updateBadge(0);

        // v1.9.2: Echte Produkt-Liste per AJAX vom Server holen (FPC-sicher)
        initFromServer();

        // Seedfinder SKU-Buttons auflösen
        initSeedfinderButtons();

        // Event Delegation für alle Vergleichen-Buttons
        document.addEventListener('click', handleCompareClick);

        // v1.9.1: Event Delegation für Clear-Buttons
        document.addEventListener('click', handleClearClick);

        // MutationObserver für dynamisch geladene Seedfinder-Karten (AJAX/Pagination)
        var observer = new MutationObserver(function(mutations) {
            var shouldUpdate = false;
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && (
                            (node.querySelector && node.querySelector('.btn-compare'))
                        )) {
                            shouldUpdate = true;
                        }
                    });
                }
            });
            if (shouldUpdate) {
                setTimeout(function() {
                    initSeedfinderButtons();
                    updateAllButtons();
                }, 200);
            }
        });

        var mainContent = document.getElementById('products-container') ||
                          document.querySelector('#content') ||
                          document.body;

        observer.observe(mainContent, {
            childList: true,
            subtree: true
        });
    }

    // DOM Ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Globale Funktion für externe Aufrufe
    window.ProductCompare = {
        toggle: toggleCompare,
        update: updateAllButtons,
        clear: clearCompare,
        // v1.9.3: clearAll() für onclick-Aufrufe im Template
        clearAll: function() { clearCompare(true); },
        getProducts: function() { return PC.currentProducts; },
        getCount: function() { return PC.currentProducts.length; },
        // v1.9.0: Cookie-Funktionen auch extern verfügbar
        saveCookie: function() { pcSaveCookie(PC.currentProducts); },
        clearCookie: pcClearCookie
    };

})();
</script>
<?php endif; ?>
