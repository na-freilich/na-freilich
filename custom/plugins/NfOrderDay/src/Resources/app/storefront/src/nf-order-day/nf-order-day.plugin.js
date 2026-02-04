import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

export default class NfOrderDay extends Plugin {

    static options = {
        url: '/nf-order-day/update'
    };

    init() {
        this._httpClient = new HttpClient();
        this.select = DomAccess.querySelector(this.el, '#nf-select-order-day', false);
        this.summary = DomAccess.querySelector(this.el, '.checkout-aside-summary-list-container', false);

        this.registerEventListener();
    }

    registerEventListener() {
        if (this.select) {
            this.select.addEventListener(
                'change',
                this._onDaySelectChanged.bind(this),
            );
        }
    }

    _onDaySelectChanged(event) {
        let selectedValues = Array.from(this.select.selectedOptions)
            .map(option => option.value);

        const formData = new FormData();
        formData.set('days', selectedValues);

        this._httpClient.post(
            this.options.url,
            formData,
            (responseText, request) => {
                this._renderSummary(responseText);
            });
    }

    _renderSummary(text){
        this.summary.innerHTML = text;
    }

}