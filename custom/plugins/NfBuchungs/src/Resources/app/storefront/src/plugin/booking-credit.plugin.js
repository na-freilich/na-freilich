import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

export default class NfBookingCreditHandler extends Plugin {

    static options = {
        selectSelector: 'select[name="nf-booking-slots-usage"]',
        applyButtonSelector: '#apply-credit-button',
        removeButtonSelector: '#remove-credit-button',
    };
    init() {
        this._client = new HttpClient();

        this._select = this.el.querySelector(this.options.selectSelector);
        this._applyButton = this.el.querySelector(this.options.applyButtonSelector);
        this._removeButton = this.el.querySelector(this.options.removeButtonSelector);

        if ((!this._select || !this._applyButton) && (!this._removeButton)){
            return;
        }

        this._registerEvents();
        this._updateButtonState();
    }

    _registerEvents() {
        if (this._select) {
            this._select.addEventListener('change', this._updateButtonState.bind(this));
        }

        if (this._applyButton) {
            this._applyButton.addEventListener('click', this._onApplyCredit.bind(this));
        }

        if (this._removeButton) {
            this._removeButton.addEventListener('click', this._onRemoveCredit.bind(this));
        }
    }

    _updateButtonState() {
        if (!this._select)
            return;

        const val = parseFloat(this._select.value);

        if (val > 0) {
            this._applyButton.disabled = false;
            this._applyButton.classList.remove('disabled');
        } else {
            this._applyButton.disabled = true;
            this._applyButton.classList.add('disabled');
        }
    }

    _onApplyCredit(event) {
        event.preventDefault();

        // this._hideErrorMessage();
        const selectedValue = this._select.value;
        if (parseFloat(selectedValue) <= 0) return;

        this._applyButton.disabled = true;
        this._applyButton.classList.add('is-loading');

        const url = '/nf-booking/credit/apply';

        this._client.post(url, JSON.stringify({ slotsCount: selectedValue }), (response) => {
            const data = JSON.parse(response);
            if (data.success) {
                const event = new CustomEvent('nf-booking-cart:refresh', {});
                document.dispatchEvent(event);

            } else {
                this._showErrorMessage(data.message);
            }

            this._applyButton.disabled = false;
            this._applyButton.classList.remove('is-loading');
        });
    }

    _onRemoveCredit(event) {
        event.preventDefault();

        this._client.post('/nf-booking/credit/remove', JSON.stringify({}), (response) => {
            const res = JSON.parse(response);
            if (res.success) {
                const event = new CustomEvent('nf-booking-cart:refresh', {});
                document.dispatchEvent(event);
            }
        });
    }

    _hideErrorMessage()
    {
        this._errorContainer.innerHTML = "";
        this._input.classList.remove('is-invalid');
    }
    _showErrorMessage(message) {
        if (!this._errorContainer) return;

        this._errorContainer.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <div class="alert-content-container">
                    <div class="alert-content">
                        ${message}
                    </div>
                </div>
            </div>
        `;
    //
        this._input.classList.add('is-invalid');
    }
}