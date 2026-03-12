<?php
/* -----------------------------------------------------------------------------------------
   Product Compare v1.1.0 - JavaScript (als .js.php für Smarty-Variablen)
   
   Hookpoint: templates/bootstrap4/javascript/extra/
   Wird automatisch auf jeder Seite geladen.
   
   v1.1.0 Fixes:
   - Seedfinder-Karten: .card.product-card Selektor (statt .listingbox .card)
   - Produkt-ID Extraktion: SEO-URL → AJAX-Lookup über Produkt-URL
   - Produkt-ID Extraktion: meta[itemprop="sku"] als Fallback
   - Button-Platzierung: Neben btn-info "Details ansehen" (kein card-footer nötig)
   - MutationObserver: Beobachtet auch .product-card Elemente
   
   @author    Mr. Hanf / Manus AI
   @version   1.1.0
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
        },
        
        // Cache: SKU → products_id Mapping (wird per AJAX gefüllt)
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
    
    // === Produkt-ID aus SKU per AJAX holen ===
    function resolveProductId(sku, callback) {
        // Prüfe Cache
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
    
    // === Produkt-ID aus einer Karte extrahieren ===
    function extractProductId(card) {
        // Methode 1: data-products-id Attribut (falls vorhanden)
        var dataId = card.getAttribute('data-products-id');
        if (dataId) return { type: 'id', value: dataId };
        
        // Methode 2: Link mit products_id= Parameter
        var link = card.querySelector('a[href*="products_id="]');
        if (link) {
            var match = link.getAttribute('href').match(/products_id=(\d+)/);
            if (match) return { type: 'id', value: match[1] };
        }
        
        // Methode 3: meta itemprop="sku" (Seedfinder product-card)
        var skuMeta = card.querySelector('meta[itemprop="sku"]');
        if (skuMeta) {
            var sku = skuMeta.getAttribute('content');
            if (sku) return { type: 'sku', value: sku };
        }
        
        // Methode 4: Produkt-URL aus dem Details-Button extrahieren
        var detailsLink = card.querySelector('a.btn-info, a.btn-primary');
        if (detailsLink) {
            var href = detailsLink.getAttribute('href');
            if (href) return { type: 'url', value: href };
        }
        
        return null;
    }
    
    // === Button erstellen ===
    function createCompareButton(productId) {
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
        
        return btn;
    }
    
    // === Button in eine Karte einfügen ===
    function insertButton(card, btn) {
        // Strategie 1: Neben dem "Details ansehen" Button (btn-info oder btn-primary)
        var detailsBtn = card.querySelector('a.btn-info, a.btn-primary');
        if (detailsBtn) {
            var wrapper = document.createElement('div');
            wrapper.className = 'd-flex justify-content-between align-items-center mt-2';
            wrapper.appendChild(btn);
            detailsBtn.parentNode.insertBefore(wrapper, detailsBtn.nextSibling);
            return;
        }
        
        // Strategie 2: In den card-footer
        var footer = card.querySelector('.card-footer');
        if (footer) {
            var wrapper2 = document.createElement('div');
            wrapper2.className = 'mt-2';
            wrapper2.appendChild(btn);
            footer.appendChild(wrapper2);
            return;
        }
        
        // Strategie 3: Am Ende der card-body
        var cardBody = card.querySelector('.card-body');
        if (cardBody) {
            var wrapper3 = document.createElement('div');
            wrapper3.className = 'mt-2 text-center';
            wrapper3.appendChild(btn);
            cardBody.appendChild(wrapper3);
            return;
        }
        
        // Strategie 4: Am Ende der Karte
        var wrapper4 = document.createElement('div');
        wrapper4.className = 'p-2 text-center';
        wrapper4.appendChild(btn);
        card.appendChild(wrapper4);
    }
    
    // === Produkt hinzufügen/entfernen ===
    function toggleCompare(productId, button) {
        var isInList = PC.currentProducts.indexOf(parseInt(productId)) !== -1;
        
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
    
    // === Buttons in Produktkarten injizieren (Seedfinder + Standard) ===
    function injectCardButtons() {
        // Alle Produktkarten finden:
        // 1. Seedfinder v5+ Karten: .card.product-card
        // 2. Seedfinder v8 Karten: .listingbox .card
        // 3. Standard modified Karten: .productbox, .product_listing .card
        var cards = document.querySelectorAll(
            '.card.product-card, ' +
            '.listingbox .card, ' +
            '.productbox, ' +
            '.product_listing .card'
        );
        
        cards.forEach(function(card) {
            // Prüfe ob schon ein Button existiert
            if (card.querySelector('.btn-compare')) return;
            
            // Produkt-ID extrahieren
            var idInfo = extractProductId(card);
            if (!idInfo) return;
            
            if (idInfo.type === 'id') {
                // Direkte ID verfügbar
                var btn = createCompareButton(idInfo.value);
                insertButton(card, btn);
                
            } else if (idInfo.type === 'sku') {
                // SKU → muss per AJAX aufgelöst werden
                (function(currentCard, sku) {
                    resolveProductId(sku, function(productId) {
                        if (productId && !currentCard.querySelector('.btn-compare')) {
                            var btn = createCompareButton(productId);
                            insertButton(currentCard, btn);
                        }
                    });
                })(card, idInfo.value);
                
            } else if (idInfo.type === 'url') {
                // URL → muss per AJAX aufgelöst werden
                (function(currentCard, url) {
                    resolveProductByUrl(url, function(productId) {
                        if (productId && !currentCard.querySelector('.btn-compare')) {
                            var btn = createCompareButton(productId);
                            insertButton(currentCard, btn);
                        }
                    });
                })(card, idInfo.value);
            }
        });
    }
    
    // === Produkt-ID aus URL per AJAX holen ===
    function resolveProductByUrl(productUrl, callback) {
        // Cache prüfen
        if (PC.skuMap['url:' + productUrl]) {
            callback(PC.skuMap['url:' + productUrl]);
            return;
        }
        
        var url = PC.ajaxUrl + '&sub_action=resolve_url&product_url=' + encodeURIComponent(productUrl);
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success && data.products_id) {
                        PC.skuMap['url:' + productUrl] = data.products_id;
                        callback(data.products_id);
                    }
                } catch(e) {
                    console.error('ProductCompare: URL resolve error', e);
                }
            }
        };
        xhr.send();
    }
    
    // === Button auf Produktseite injizieren ===
    function injectProductPageButton() {
        var addToCartForm = document.querySelector('form[name="cart_quantity"]');
        if (!addToCartForm) return;
        
        var productId = null;
        
        // Aus URL (products_id= Parameter)
        var urlMatch = window.location.href.match(/products_id=(\d+)/);
        if (urlMatch) {
            productId = urlMatch[1];
        }
        
        // Aus Formular (hidden field)
        if (!productId) {
            var hiddenField = addToCartForm.querySelector('input[name="products_id"]');
            if (hiddenField) productId = hiddenField.value;
        }
        
        // Aus meta-Tag auf der Seite
        if (!productId) {
            var skuMeta = document.querySelector('meta[itemprop="sku"]');
            if (skuMeta) {
                var sku = skuMeta.getAttribute('content');
                resolveProductId(sku, function(pid) {
                    if (pid) insertProductPageBtn(addToCartForm, pid);
                });
                return;
            }
        }
        
        if (!productId) return;
        insertProductPageBtn(addToCartForm, productId);
    }
    
    function insertProductPageBtn(form, productId) {
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
        
        var cartButton = form.querySelector('button[type="submit"], input[type="submit"], .btn-cart, #cart_button');
        if (cartButton) {
            cartButton.parentNode.insertBefore(wrapper, cartButton.nextSibling);
        } else {
            form.appendChild(wrapper);
        }
    }
    
    // === Initialisierung ===
    function init() {
        updateBadge(PC.currentProducts.length);
        
        // Buttons injizieren
        injectCardButtons();
        injectProductPageButton();
        
        // MutationObserver für dynamisch geladene Inhalte (Seedfinder AJAX/Pagination)
        var observer = new MutationObserver(function(mutations) {
            var shouldUpdate = false;
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && (
                            node.classList && (
                                node.classList.contains('listingbox') ||
                                node.classList.contains('product-card') ||
                                node.classList.contains('col-md-6') ||
                                node.classList.contains('row')
                            ) || node.querySelector && (
                                node.querySelector('.product-card') ||
                                node.querySelector('.listingbox')
                            )
                        )) {
                            shouldUpdate = true;
                        }
                    });
                }
            });
            if (shouldUpdate) {
                setTimeout(function() {
                    injectCardButtons();
                }, 200);
            }
        });
        
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
