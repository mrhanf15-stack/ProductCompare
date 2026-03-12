<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v1.0.0 - JavaScript (als .js.php für Smarty-Variablen)
   
   Hookpoint: templates/bootstrap4/javascript/extra/
   Wird automatisch auf jeder Seite geladen.
   
   Funktionen:
   - Vergleichen-Buttons in Seedfinder-Karten und Produktseiten injizieren
   - AJAX: Produkt hinzufügen/entfernen
   - Floating Badge aktualisieren
   - Toast-Benachrichtigungen
   
   @author    Mr. Hanf / Manus AI
   @version   1.0.0
   @date      2026-03-12
   -----------------------------------------------------------------------------------------*/

if (defined('MODULE_PRODUCT_COMPARE_STATUS') && MODULE_PRODUCT_COMPARE_STATUS == 'true'):
?>
<link rel="stylesheet" href="templates/bootstrap4/css/product_compare.css">
<script>
(function() {
    'use strict';
    
    // === Konfiguration ===
    var PC = {
        ajaxUrl: 'ajax.php?action=product_compare',
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
        }
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
        
        // Trigger reflow
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
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (callback) callback(data);
                    } catch(e) {
                        console.error('ProductCompare: JSON parse error', e);
                    }
                }
            }
        };
        xhr.send();
    }
    
    // === Produkt hinzufügen/entfernen ===
    function toggleCompare(productId, button) {
        var isInList = PC.currentProducts.indexOf(parseInt(productId)) !== -1;
        
        if (isInList) {
            // Entfernen
            ajaxCompare('remove', productId, function(data) {
                if (data.success) {
                    PC.currentProducts = data.products.map(Number);
                    updateBadge(data.count);
                    updateAllButtons();
                    showToast(PC.text.msgRemoved, 'info');
                }
            });
        } else {
            // Hinzufügen
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
    
    // === Button-Status aktualisieren ===
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
    
    // === Buttons in Seedfinder-Karten injizieren ===
    function injectSeedfinderButtons() {
        // Seedfinder Produktkarten: .listingbox .card
        var cards = document.querySelectorAll('.listingbox .card');
        
        cards.forEach(function(card) {
            // Prüfe ob schon ein Button existiert
            if (card.querySelector('.btn-compare')) return;
            
            // Produkt-ID aus dem Link extrahieren
            var link = card.querySelector('a[href*="products_id="]');
            if (!link) return;
            
            var href = link.getAttribute('href');
            var match = href.match(/products_id=(\d+)/);
            if (!match) return;
            
            var productId = match[1];
            
            // Button erstellen
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn-compare';
            btn.setAttribute('data-product-id', productId);
            btn.innerHTML = '<span class="fa fa-balance-scale mr-1"></span>' + PC.text.add;
            
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleCompare(productId, btn);
            });
            
            // Button in den card-footer einfügen (vor dem Details-Button)
            var footer = card.querySelector('.card-footer');
            if (footer) {
                var detailsBtn = footer.querySelector('.btn-primary');
                if (detailsBtn) {
                    // Wrapper für beide Buttons
                    var wrapper = document.createElement('div');
                    wrapper.className = 'd-flex justify-content-between align-items-center mt-2';
                    wrapper.appendChild(btn);
                    
                    // "Jetzt vergleichen" Mini-Link
                    if (PC.currentProducts.indexOf(parseInt(productId)) !== -1) {
                        btn.classList.add('active');
                        btn.innerHTML = '<span class="fa fa-check mr-1"></span>' + PC.text.added;
                    }
                    
                    footer.appendChild(wrapper);
                }
            }
        });
    }
    
    // === Buttons in Standard-Produktlisten injizieren ===
    function injectListingButtons() {
        // Standard modified product_listing Karten
        var productBoxes = document.querySelectorAll('.productbox, .product-card');
        
        productBoxes.forEach(function(box) {
            if (box.querySelector('.btn-compare')) return;
            
            var link = box.querySelector('a[href*="products_id="]');
            if (!link) return;
            
            var href = link.getAttribute('href');
            var match = href.match(/products_id=(\d+)/);
            if (!match) return;
            
            var productId = match[1];
            var isInList = PC.currentProducts.indexOf(parseInt(productId)) !== -1;
            
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn-compare' + (isInList ? ' active' : '');
            btn.setAttribute('data-product-id', productId);
            btn.innerHTML = isInList 
                ? '<span class="fa fa-check mr-1"></span>' + PC.text.added
                : '<span class="fa fa-balance-scale mr-1"></span>' + PC.text.add;
            
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleCompare(productId, btn);
            });
            
            // Einfügen nach dem letzten Button oder am Ende
            var actionArea = box.querySelector('.card-footer, .product-actions, .lb_actions');
            if (actionArea) {
                actionArea.appendChild(btn);
            }
        });
    }
    
    // === Button auf Produktseite injizieren ===
    function injectProductPageButton() {
        // Prüfe ob wir auf einer Produktseite sind
        var addToCartForm = document.querySelector('form[name="cart_quantity"]');
        if (!addToCartForm) return;
        
        // Produkt-ID aus der URL oder dem Formular extrahieren
        var productId = null;
        
        // Aus URL
        var urlMatch = window.location.href.match(/products_id=(\d+)/);
        if (urlMatch) {
            productId = urlMatch[1];
        }
        
        // Aus Formular (hidden field)
        if (!productId) {
            var hiddenField = addToCartForm.querySelector('input[name="products_id"]');
            if (hiddenField) productId = hiddenField.value;
        }
        
        if (!productId) return;
        if (document.querySelector('.product-compare-btn-wrapper')) return;
        
        var isInList = PC.currentProducts.indexOf(parseInt(productId)) !== -1;
        
        var wrapper = document.createElement('div');
        wrapper.className = 'product-compare-btn-wrapper d-inline-block ml-2';
        
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-compare btn' + (isInList ? ' active' : '');
        btn.setAttribute('data-product-id', productId);
        btn.innerHTML = isInList
            ? '<span class="fa fa-check mr-1"></span>' + PC.text.added
            : '<span class="fa fa-balance-scale mr-1"></span>' + PC.text.add;
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            toggleCompare(productId, btn);
        });
        
        wrapper.appendChild(btn);
        
        // Neben dem "In den Warenkorb" Button einfügen
        var cartButton = addToCartForm.querySelector('button[type="submit"], input[type="submit"], .btn-cart, #cart_button');
        if (cartButton) {
            cartButton.parentNode.insertBefore(wrapper, cartButton.nextSibling);
        } else {
            addToCartForm.appendChild(wrapper);
        }
    }
    
    // === Initialisierung ===
    function init() {
        // Badge initialisieren
        updateBadge(PC.currentProducts.length);
        
        // Buttons injizieren
        injectSeedfinderButtons();
        injectListingButtons();
        injectProductPageButton();
        
        // MutationObserver für dynamisch geladene Inhalte (Seedfinder AJAX)
        var observer = new MutationObserver(function(mutations) {
            var shouldUpdate = false;
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && (
                            node.classList && (
                                node.classList.contains('listingbox') ||
                                node.classList.contains('row')
                            ) || node.querySelector && node.querySelector('.listingbox')
                        )) {
                            shouldUpdate = true;
                        }
                    });
                }
            });
            if (shouldUpdate) {
                setTimeout(function() {
                    injectSeedfinderButtons();
                    injectListingButtons();
                }, 100);
            }
        });
        
        // Beobachte den Hauptinhalt
        var mainContent = document.getElementById('products-container') || 
                          document.querySelector('.main-content') ||
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
