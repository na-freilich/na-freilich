import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';

export default class NfBookingCartPlugin extends Plugin {
    static options = {
        locationId: null,
        productId: null,
        date: null,
        endpoint: '/nf-booking/product-cart',
        endpointItemDel: '/nf-booking/item/delete',
        endpointExpired: '/nf-booking/check-expired',
        deleteButtonSelector: '.del-btn',
        cartItemSelector: '.list-group-item'
    };

    init() {
        this._client = new HttpClient();
        this._registerEvents();
        this.refreshCart();
    }

    _registerEvents() {
        document.addEventListener('nf-booking-cart:refresh', this._onRefreshCart.bind(this));
        this.el.addEventListener('click', this._onButtonClick.bind(this));
        document.removeEventListener('nf-booking-timer:expired', this._onBookingExpired.bind(this));
        document.addEventListener('nf-booking-timer:expired', this._onBookingExpired.bind(this));
    }

    _onRefreshCart(event) {
        this.refreshCart();
    }

    _onBookingExpired() {
        const url =
            `${this.options.endpointExpired}`;

        setTimeout(() => {
            const url = `${this.options.endpointExpired}`;

            this._client.get(url, (response) => {
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        this.refreshCart();
                        const event = new CustomEvent('nf-booking:refresh', {
                            bubbles: true,
                            cancelable: true
                        });
                        this.el.dispatchEvent(event);
                    }
                } catch (e) {
                    console.error("Error checking expired reservation", e);
                }
            });
        }, 1000);
    }

    _onButtonClick(event) {
        const btn = event.target.closest(this.options.deleteButtonSelector);
        if (!btn) return;

        event.preventDefault();
        event.stopPropagation();

        this._handleDelete(btn);
    }
    refreshCart() {
        this.el.style.opacity = '0.5';
        const url =
            `${this.options.endpoint}?productId=${this.options.productId}`;
        this.el.innerHTML = '<div class="loader">...</div>';
        this._client.get(url, (response) => {
            this.el.innerHTML = response;
            this.el.style.opacity = '1';
            //
            window.PluginManager.initializePlugins();
            this._autoSelectDate();
        });
    }

    _autoSelectDate() {

        const header = this.el.querySelector('.card-header');
        const firstDate = header ? header.dataset.firstDate : null;

        if (firstDate) {
            const event = new CustomEvent('nf-booking:initCartDate', {
                detail: { date: firstDate },
                bubbles: true
            });

            this.el.dispatchEvent(event);
        }
    }

    _handleDelete(btn) {
        const itemId = btn.dataset.bookingItemId;

        const payload = {
            itemId: itemId,
            productId: this.options.productId
        };

        const url =
            `${this.options.endpointItemDel}`;

        const lineItem = btn.closest(this.options.cartItemSelector);
        if (lineItem) {
            ElementLoadingIndicatorUtil.create(lineItem);
        }

        this._client.post(url, JSON.stringify(payload), (response) => {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    this.refreshCart();

                    const event = new CustomEvent('nf-booking-item:delete', {
                        detail: { itemId: itemId },
                        bubbles: true
                    });

                    this.el.dispatchEvent(event);
                } else {
                    this._showError(lineItem);
                }
            } catch (e) {
                this._showError(lineItem);
            }
        });

    }

    _showError(lineItem) {
        if (lineItem) {
            ElementLoadingIndicatorUtil.remove(lineItem);
        }
        alert('Could not remove booking. Please try again.');
    }


}