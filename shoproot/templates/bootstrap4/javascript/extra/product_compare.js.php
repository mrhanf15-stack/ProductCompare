<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v1.2.2 - JavaScript (als .js.php für Smarty-Variablen)
   
   Hookpoint: templates/bootstrap4/javascript/extra/
   Wird automatisch auf jeder Seite geladen.
   
   v1.2.0 Neuer Ansatz:
   - Buttons werden direkt in den Smarty-Templates platziert (nicht mehr per JS-Injection)
   - Produktseite: Kleiner Merkzettel-Button wird durch Vergleichen-Button ersetzt
   - Seedfinder-Karten: Button direkt im Template
   - Standard-Listings: Button direkt im Template
   - JavaScript nur noch für: AJAX-Kommunikation, Badge-Update, Toast, Button-Status
   
   @author    Mr. Hanf / Manus AI
   @version   1.2.2
   @date      2026-03-12
   -----------------------------------------------------------------------------------------*/

if (defined('MODULE_PRODUCT_COMPARE_STATUS') && MODULE_PRODUCT_COMPARE_STATUS == 'true'):
?>
<link rel="stylesheet" href="<?php echo (defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/'); ?>templates/bootstrap4/css/product_compare.css">
<script>
(function() {
    'use strict';
    
    // === Konfiguration ===
    var PC = {
        ajaxUrl: '<?php echo (defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/'); ?>ajax.php?ext=product_compare',
        compareUrl: '<?php echo xtc_href_link("product_compare.php"); ?>',
        maxProducts: <?php echo (defined('MODULE_PRODUCT_COMPARE_MAX_PRODUCTS') ? (int)MODULE_PRODUCT_COMPARE_MAX_PRODUCTS : 6); ?>,
        currentProducts: <?php echo json_encode(isset($_SESSION['product_compare']) ? array_values($_SESSION['product_compare']) : array()); ?>,
        
        // Texte
        text: {
            add: '<?php echo addslashes(defined("PC_BUTTON_ADD") ? PC_BUTTON_ADD : "Vergleichen"); ?>',
            added: '<?php echo addslashes(defined("PC_BUTTON_ADDED") ? PC_BUTTON_ADDED : "Im Vergleich"); ?>',
            compareNow: '<?php echo addslashes(defined("PC_BUTTON_COMPARE_NOW") ? PC_BUTTON_COMPARE_NOW : "Jetzt vergleichen"); ?>',
            msgAdded: '<?php echo addslashes(defined("PC_MSG_ADDED") ? PC_MSG_ADDED : "Produkt zum Vergleich hinzugefügt"); ?>',
            msgRemoved: '<?php echo addslashes(defined("PC_MSG_REMOVED") ? PC_MSG_REMOVED : "Produkt aus dem Vergleich entfernt"); ?>',
            msgAlready: '<?php echo addslashes(defined("PC_MSG_ALREADY") ? PC_MSG_ALREADY : "Produkt ist bereits im Vergleich"); ?>',
            msgMaxReached: '<?php echo addslashes(defined("PC_MSG_MAX_REACHED") ? str_replace("%s", (defined("MODULE_PRODUCT_COMPARE_MAX_PRODUCTS") ? MODULE_PRODUCT_COMPARE_MAX_PRODUCTS : "6"), PC_MSG_MAX_REACHED) : "Maximale Anzahl erreicht"); ?>'
        },
        
        // Cache: SKU → products_id Mapping
        skuMap: {}
    };
    
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
    
    // === Initialisierung ===
    function init() {
        // Badge initialisieren
        updateBadge(PC.currentProducts.length);
        
        // Initiale Button-Status setzen
        updateAllButtons();
        
        // Seedfinder SKU-Buttons auflösen
        initSeedfinderButtons();
        
        // Event Delegation für alle Vergleichen-Buttons
        document.addEventListener('click', handleCompareClick);
        
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
        getProducts: function() { return PC.currentProducts; },
        getCount: function() { return PC.currentProducts.length; }
    };
    
})();
</script>
<?php endif; ?>
