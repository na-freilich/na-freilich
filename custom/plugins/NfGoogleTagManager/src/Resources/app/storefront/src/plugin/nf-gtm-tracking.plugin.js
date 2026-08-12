import Plugin from 'src/plugin-system/plugin.class';

export default class NfGtmTrackingPlugin extends Plugin {
    init() {
        this._registerEvents();
    }

    _registerEvents() {
        this.el.addEventListener('submit', this._onFormSubmit.bind(this));

        document.addEventListener('HttpClient.onLoad', this._onOffCanvasLoaded.bind(this));

        document.addEventListener('click', this._onRemoveItemClick.bind(this));

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', this._initWishlistTracking.bind(this));
        } else {
            this._initWishlistTracking();
        }
    }

    _onFormSubmit(event) {
        const form = event.target;

        const gtmInput = form.querySelector('.js-nf-gtm-track-item');
        if (!gtmInput || !gtmInput.dataset.nfGtmItem) {
            return;
        }

        try {
            const itemData = JSON.parse(gtmInput.dataset.nfGtmItem);

            const quantityInput = form.querySelector('input[name^="lineItems"][name$="[quantity]"]');
            const quantity = quantityInput ? parseInt(quantityInput.value, 10) : 1;

            itemData.quantity = quantity;

            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({
                'event': 'add_to_cart',
                'ecommerce': {
                    'currency': document.documentElement.dataset.nfCurrency || 'EUR',
                    'value': parseFloat((itemData.price * quantity).toFixed(2)),
                    'items': [itemData]
                }
            });

            this._log('The add_to_cart push event was successfully executed', itemData);

        } catch (e) {
            console.error('GTM: Error processing the add_to_cart event', e);
        }
    }

    _onOffCanvasLoaded(event) {
        const offCanvasDataEl = document.getElementById('js-nf-gtm-offcanvas-data');
        if (!offCanvasDataEl) {
            return;
        }

        if (window.nfGtmLastOffCanvasTime && (Date.now() - window.nfGtmLastOffCanvasTime < 1000)) {
            return;
        }
        window.nfGtmLastOffCanvasTime = Date.now();

        try {
            const items = JSON.parse(offCanvasDataEl.dataset.nfCartItems);
            const cartValue = parseFloat(offCanvasDataEl.dataset.nfCartValue);

            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({
                'event': 'view_cart',
                'ecommerce': {
                    'currency': document.documentElement.dataset.nfCurrency || 'EUR',
                    'value': cartValue,
                    'items': items
                }
            });

            this._log('`view_cart` push event (Off-Canvas) successfully executed', items);
        } catch (e) {
            console.error('GTM: Off-Canvas cart parsing error', e);
        }
    }

    _onRemoveItemClick(event) {
        const removeButton = event.target.closest('.line-item-remove-button');
        if (!removeButton) {
            return;
        }

        const lineItemRow = removeButton.closest('.line-item-row');
        if (!lineItemRow) {
            return;
        }

        const offCanvasDataEl = document.getElementById('js-nf-gtm-offcanvas-data');
        if (!offCanvasDataEl) {
            return;
        }

        try {
            const items = JSON.parse(offCanvasDataEl.dataset.nfCartItems);

            const labelEl = lineItemRow.querySelector('.line-item-label');
            const itemLabel = labelEl ? labelEl.innerText.trim() : '';

            const removedItem = items.find(item => item.item_name === itemLabel);

            if (removedItem) {
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    'event': 'remove_from_cart',
                    'ecommerce': {
                        'currency': document.documentElement.dataset.nfCurrency || 'EUR',
                        'value': parseFloat((removedItem.price * removedItem.quantity).toFixed(2)),
                        'items': [removedItem]
                    }
                });

                this._log('remove_from_cart` push successful', removedItem);
            }
        } catch (e) {
            console.error('GTM: Error processing remove_from_cart', e);
        }
    }

    _initWishlistTracking() {
        const wishlistBasketElement = document.querySelector('#wishlist-basket');
        if (!wishlistBasketElement) {
            return;
        }

        const wishlistStorage = window.PluginManager.getPluginInstanceFromElement(wishlistBasketElement, 'WishlistStorage');
        if (!wishlistStorage || !wishlistStorage.$emitter) {
            return;
        }

        wishlistStorage.$emitter.subscribe('Wishlist/onProductAdded', (data) => {
            if (data && data.detail.productId) {
                this._trackWishlistAction(data.detail.productId);
            }
        });
    }

    _trackWishlistAction(productId) {
        try {

            const wishlistBtn = document.querySelector(`.js-nf-gtm-wishlist-btn[data-nf-gtm-id="${productId}"]`);
            let itemName = productId;
            let productNumber = wishlistBtn.dataset.nfGtmSku || productId;
            let price = 0;

            if (wishlistBtn) {
                const productBox = wishlistBtn.closest('.product-box');
                const listingName = productBox ? productBox.querySelector('.product-name') : null;
                const pdpH1 = document.querySelector('.product-detail-name');

                if (pdpH1 && wishlistBtn.closest('.is-ctl-product')) {
                    itemName = pdpH1.innerText.trim();
                    if (!productNumber)
                    {
                        const skuEl = document.querySelector('[itemprop="sku"]');
                        productNumber = skuEl ? skuEl.innerText.trim() : '';
                    }
                } else if (listingName) {
                    itemName = listingName.innerText.trim();
                }

                const priceEl = productBox ? productBox.querySelector('.product-price') : document.querySelector('.product-detail-price');
                if (priceEl) {
                    const priceText = priceEl.innerText.replace(/[^\d.,]/g, '').replace(',', '.');
                    price = parseFloat(priceText) || 0;
                }
            }

            const wishlistData = {
                'item_id': productNumber,
                'item_name': itemName,
                'price': price,
                'quantity': 1
            };

            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({
                'event': 'add_to_wishlist',
                'ecommerce': {
                    'currency': document.documentElement.dataset.nfCurrency || 'EUR',
                    'value': price,
                    'items': [wishlistData]
                }
            });

            this._log('Add to wishlist" push notification sent successfully', wishlistData);
        } catch (e) {
            console.error('GTM: wishlist tracking error', e);
        }
    }

    _log(message, data = null) {
        const configEl = document.getElementById('nf-gtm-config');
        const isDebugEnabled = configEl ? configEl.getAttribute('data-debug') === '1' : false;

        if (isDebugEnabled) {
            if (data) {
                console.log(`[GTM DEBUG] ${message}`, data);
            } else {
                console.log(`[GTM DEBUG] ${message}`);
            }
        }
    }
}