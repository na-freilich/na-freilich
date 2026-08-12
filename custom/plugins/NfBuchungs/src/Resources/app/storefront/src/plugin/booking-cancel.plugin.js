import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

export default class BookingCancelPlugin extends Plugin {
    init() {
        this._client = new HttpClient();
        this._registerEvents();
    }

    _registerEvents() {
        this.el.addEventListener('submit', this._onSubmit.bind(this));
    }

    _onSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const submitBtn = form.querySelector('[type="submit"]');
        const formData = new FormData(form);

        submitBtn.disabled = true;
        const oldBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

        this._client.post(form.action, formData, (response) => {
            try {
                const data = JSON.parse(response);

                if (data.success) {
                    window.location.reload();
                } else {
                    this._showError(data.message || 'Error occurred');
                    this._resetButton(submitBtn, oldBtnText);
                }
            } catch (e) {
                this._showError('System error');
                this._resetButton(submitBtn, oldBtnText);
            }
        });
    }

    _resetButton(btn, text) {
        btn.disabled = false;
        btn.innerHTML = text;
    }

    _showError(message) {
        alert(message);
    }
}