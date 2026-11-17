import Plugin from 'src/plugin-system/plugin.class';

export default class NfGtmTrackingPlugin extends Plugin {
    init() {
        this._registerEvents();
        this._bindAddToCartPlugins();
        this._bindOffCanvas();

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', this._initWishlistTracking.bind(this));
        } else {
            this._initWishlistTracking();
        }
    }

    _registerEvents() {
        document.addEventListener('submit', this._onFormSubmit.bind(this), true);
        document.addEventListener('submit', this._onRemoveSubmit.bind(this), true);
        document.addEventListener('click', this._onSelectItemClick.bind(this));
    }

    _bindAddToCartPlugins() {
        const instances = window.PluginManager.getPluginInstances('AddToCart') || [];
        instances.forEach((instance) => {
            if (!instance.$emitter || instance.el.dataset.nfGtmAddBound === '1') {
                return;
            }
            instance.el.dataset.nfGtmAddBound = '1';
            instance.$emitter.subscribe('beforeFormSubmit', () => {
                const form = instance.el && instance.el.tagName === 'FORM'
                    ? instance.el
                    : instance.el.closest('form');
                if (form) {
                    this._pushAddToCart(form);
                }
            });
        });
    }

    _bindOffCanvas() {
        const instances = window.PluginManager.getPluginInstances('OffCanvasCart') || [];
        instances.forEach((instance) => {
            if (!instance.$emitter || instance.el.dataset.nfGtmOffcanvasBound === '1') {
                return;
            }
            instance.el.dataset.nfGtmOffcanvasBound = '1';
            instance.$emitter.subscribe('offCanvasOpened', () => {
                window.setTimeout(() => this._pushOffCanvasCart(), 0);
            });
        });
    }

    _onFormSubmit(event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-add-to-cart')) {
            return;
        }

        this._pushAddToCart(form);
    }

    _pushAddToCart(form) {
        if (!this._flag('add') || form.dataset.nfGtmAddTracked === '1') {
            return;
        }

        const item = this._itemFromForm(form);
        if (!item) {
            return;
        }

        form.dataset.nfGtmAddTracked = '1';
        window.setTimeout(() => {
            delete form.dataset.nfGtmAddTracked;
        }, 500);

        const quantityInput = form.querySelector('input[name^="lineItems"][name$="[quantity]"]');
        const quantity = quantityInput ? parseInt(quantityInput.value, 10) : (item.quantity || 1);
        item.quantity = Number.isNaN(quantity) ? 1 : quantity;
        this._setListIndex(form, item);

        const price = parseFloat(item.price) || 0;
        this._pushEcommerce('add_to_cart', price * item.quantity, [item]);
        this._log('The add_to_cart push event was successfully executed', item);
    }

    _onSelectItemClick(event) {
        if (!this._flag('select')) {
            return;
        }

        const link = event.target.closest('.product-box a');
        if (!link || link.closest('form[data-add-to-cart]')) {
            return;
        }

        const box = link.closest('.product-box');
        const input = box ? box.querySelector('.js-nf-gtm-track-item') : null;
        if (!input || !input.dataset.nfGtmItem) {
            return;
        }

        const item = this._parseItem(input.dataset.nfGtmItem);
        if (!item) {
            return;
        }

        this._setListIndex(box, item);
        const price = parseFloat(item.price) || 0;
        this._pushEcommerce('select_item', price, [item]);
        this._log('select_item push successful', item);
    }

    _pushOffCanvasCart() {
        const offCanvasDataEl = document.getElementById('js-nf-gtm-offcanvas-data');
        if (!offCanvasDataEl) {
            return;
        }

        if (window.nfGtmLastOffCanvasTime && (Date.now() - window.nfGtmLastOffCanvasTime < 1000)) {
            return;
        }
        window.nfGtmLastOffCanvasTime = Date.now();

        const items = this._parseItem(offCanvasDataEl.dataset.nfCartItems);
        const cartValue = parseFloat(offCanvasDataEl.dataset.nfCartValue);
        if (!Array.isArray(items) || items.length === 0) {
            return;
        }

        this._pushEcommerce('view_cart', cartValue, items);
        this._log('view_cart push event (Off-Canvas) successfully executed', items);
    }

    _onRemoveSubmit(event) {
        if (!this._flag('remove')) {
            return;
        }

        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const button = form.querySelector('.line-item-remove-button');
        if (!button || !button.dataset.nfGtmItem) {
            return;
        }

        const item = this._parseItem(button.dataset.nfGtmItem);
        if (!item) {
            return;
        }

        const ajax = form.classList.contains('js-offcanvas-cart-remove-product');
        if (ajax || !window.google_tag_manager) {
            this._pushRemove(item);
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        this._pushRemove(item, () => form.submit());
    }

    _pushRemove(item, done) {
        const price = parseFloat(item.price) || 0;
        const quantity = parseInt(item.quantity, 10) || 1;
        const payload = this._ecommercePayload('remove_from_cart', price * quantity, [item]);

        if (typeof done === 'function') {
            let finished = false;
            const finish = () => {
                if (finished) {
                    return;
                }
                finished = true;
                done();
            };
            payload.eventCallback = finish;
            payload.eventTimeout = 1000;
            window.setTimeout(finish, 1200);
        }

        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ ecommerce: null });
        window.dataLayer.push(payload);
        this._log('remove_from_cart push successful', item);
    }

    _itemFromForm(form) {
        const box = form.closest('.product-box');
        const input = form.querySelector('.js-nf-gtm-track-item')
            || (box ? box.querySelector('.js-nf-gtm-track-item') : null);
        if (!input || !input.dataset.nfGtmItem) {
            return null;
        }

        return this._parseItem(input.dataset.nfGtmItem);
    }

    _setListIndex(element, item) {
        if (!element || item.index) {
            return;
        }

        const box = element.classList && element.classList.contains('product-box')
            ? element
            : element.closest('.product-box');
        if (!box) {
            return;
        }

        const listing = box.closest('.cms-element-product-listing, .cms-element-product-slider') || box.parentElement;
        if (!listing) {
            return;
        }

        const boxes = Array.from(listing.querySelectorAll('.product-box'));
        const index = boxes.indexOf(box);
        if (index >= 0) {
            item.index = index + 1;
        }
    }

    _pushEcommerce(eventName, value, items) {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ ecommerce: null });
        window.dataLayer.push(this._ecommercePayload(eventName, value, items));
    }

    _ecommercePayload(eventName, value, items) {
        return {
            event: eventName,
            ecommerce: {
                currency: document.documentElement.dataset.nfCurrency || 'EUR',
                value: parseFloat((Number(value) || 0).toFixed(2)),
                items: items,
            },
        };
    }

    _parseItem(raw) {
        try {
            return JSON.parse(raw);
        } catch (e) {
            console.error('GTM: item parsing error', e);
            return null;
        }
    }

    _flag(name) {
        const configEl = document.getElementById('nf-gtm-config');
        if (!configEl) {
            return false;
        }

        return configEl.getAttribute(`data-${name}-active`) === '1';
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
            let productNumber = wishlistBtn ? wishlistBtn.dataset.nfGtmSku || productId : productId;
            let price = 0;

            if (wishlistBtn) {
                const productBox = wishlistBtn.closest('.product-box');
                const listingName = productBox ? productBox.querySelector('.product-name') : null;
                const pdpH1 = document.querySelector('.product-detail-name');

                if (pdpH1 && wishlistBtn.closest('.is-ctl-product')) {
                    itemName = pdpH1.innerText.trim();
                    if (!productNumber) {
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
                item_id: productNumber,
                item_name: itemName,
                price: price,
                quantity: 1,
            };

            this._pushEcommerce('add_to_wishlist', price, [wishlistData]);
            this._log('Add to wishlist push notification sent successfully', wishlistData);
        } catch (e) {
            console.error('GTM: wishlist tracking error', e);
        }
    }

    _log(message, data = null) {
        const configEl = document.getElementById('nf-gtm-config');
        const isDebugEnabled = configEl ? configEl.getAttribute('data-debug') === '1' : false;

        if (!isDebugEnabled) {
            return;
        }

        if (data) {
            console.log(`[GTM DEBUG] ${message}`, data);
            return;
        }

        console.log(`[GTM DEBUG] ${message}`);
    }
}
