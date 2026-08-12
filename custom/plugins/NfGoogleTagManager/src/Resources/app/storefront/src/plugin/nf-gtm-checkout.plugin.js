import Plugin from 'src/plugin-system/plugin.class';

export default class NfGtmCheckoutPlugin extends Plugin {
    init() {
        this._checkoutItems = JSON.parse(this.el.dataset.nfCheckoutItems || '[]');
        this._checkoutValue = parseFloat(this.el.dataset.nfCheckoutValue || 0);
        this._currency = document.documentElement.dataset.nfCurrency || 'EUR';

        this._registerEvents();
    }

    _registerEvents() {
        const shippingForm = document.getElementById('changeShippingForm');
        if (shippingForm) {
            shippingForm.addEventListener('change', this._onShippingChange.bind(this));
        }

        const paymentForm = document.getElementById('changePaymentForm');
        if (paymentForm) {
            paymentForm.addEventListener('change', this._onPaymentChange.bind(this));
        }
    }

    _onShippingChange(event) {
        const radio = event.target;
        if (radio.type !== 'radio' || !radio.checked) return;

        const labelEl = radio.closest('.form-check').querySelector('.form-check-label');
        const shippingName = labelEl ? labelEl.innerText.trim() : 'Unknown';

        this._pushToDataLayer('add_shipping_info', {
            shipping_tier: shippingName
        });
    }

    _onPaymentChange(event) {
        const radio = event.target;
        if (radio.type !== 'radio' || !radio.checked) return;

        const labelEl = radio.closest('.form-check').querySelector('.form-check-label');
        const paymentName = labelEl ? labelEl.innerText.trim() : 'Unknown';

        this._pushToDataLayer('add_payment_info', {
            payment_method: paymentName
        });
    }

    _pushToDataLayer(eventName, additionalParams) {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ ecommerce: null });
        window.dataLayer.push({
            'event': eventName,
            'ecommerce': {
                'currency': this._currency,
                'value': this._checkoutValue,
                'items': this._checkoutItems,
                ...additionalParams
            }
        });

        this._log(`Event ${eventName} successfully sent`, additionalParams);
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