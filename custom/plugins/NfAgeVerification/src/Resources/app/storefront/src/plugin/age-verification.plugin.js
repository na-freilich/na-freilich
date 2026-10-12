import Plugin from 'src/plugin-system/plugin.class';
import CookieStorage from 'src/helper/storage/cookie-storage.helper';

export default class AgeVerificationPlugin extends Plugin {
    init() {
        this.cookieName = 'nf_is_adult';
        this.modal = this.el.querySelector('#nf-age-verification-modal');
        this.confirmBtn = this.el.querySelector('#nf-age-confirm-btn');
        this.rejectBtn = this.el.querySelector('#nf-age-reject-btn');

        this._syncGate();
        this._registerEvents();
        this._registerPurchaseGuard();
    }

    _cookieValue() {
        return CookieStorage.getItem(this.cookieName);
    }

    _isAdult() {
        return this._cookieValue() === '1';
    }

    _syncGate() {
        const value = this._cookieValue();

        if (value === '0') {
            document.documentElement.classList.add('nf-age-rejected');
            document.documentElement.classList.remove('nf-age-gate-open');
            this._hideModal();
            return;
        }

        if (value === '1') {
            document.documentElement.classList.remove('nf-age-rejected', 'nf-age-gate-open');
            this._hideModal();
            return;
        }

        this._showModal();
    }

    _showModal() {
        document.documentElement.classList.add('nf-age-gate-open');

        if (this.modal) {
            this.modal.style.display = 'flex';
        }
    }

    _hideModal() {
        document.documentElement.classList.remove('nf-age-gate-open');

        if (this.modal) {
            this.modal.style.display = 'none';
        }

        document.body.style.overflow = '';
    }

    _remember(value) {
        CookieStorage.setItem(this.cookieName, value, 30);
        document.documentElement.classList.toggle('nf-age-rejected', value === '0');
        this._hideModal();
    }

    _registerEvents() {
        if (this.confirmBtn) {
            this.confirmBtn.addEventListener('click', () => {
                this._remember('1');
            });
        }

        if (this.rejectBtn) {
            this.rejectBtn.addEventListener('click', () => {
                this._remember('0');
            });
        }
    }

    _registerPurchaseGuard() {
        document.addEventListener('click', (event) => this._blockPurchase(event), true);
        document.addEventListener('submit', (event) => this._blockPurchase(event), true);
    }

    _blockPurchase(event) {
        if (this._isAdult()) {
            return;
        }

        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const zone = target.closest('.product-box, form.buy-widget, .cms-element-buy-box');
        if (!zone || !zone.querySelector('[data-nf-age-restricted]')) {
            return;
        }

        const control = target.closest('.btn-buy, button[type="submit"], [data-add-to-cart="true"]');
        if (event.type !== 'submit' && !control) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
    }
}
