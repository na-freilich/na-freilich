import Plugin from 'src/plugin-system/plugin.class';
import Storage from 'src/helper/storage/storage.helper';
import HttpClient from 'src/service/http-client.service';
import StringHelper from 'src/helper/string.helper';

export default class nfVisitorsStatisticPlugin extends Plugin {

    /**
     * Plugin initializer
     *
     * @returns {void}
     */
    static options = {
        url: '/nf-statistics/update',
        visitorsKey: 'nf-visitor',
    };

    init() {
        this._httpClient = new HttpClient();
        this.updateStatistic();
    }

    updateStatistic() {
        const userAgent = navigator.userAgent;

        if (userAgent && /googlebot|bot|robot/i.test(userAgent)) {
            return;
        }

        let visitorsKey = Storage.getItem(this.options.visitorsKey);
        if (!visitorsKey) {
            visitorsKey = this.createUUID();
            Storage.setItem(this.options.visitorsKey, visitorsKey);
        }
        const dashedPluginName = StringHelper.toDashCase(this._pluginName);
        const formData = new FormData();
        formData.set('key', visitorsKey);
        this._httpClient.post(
            this.options.url,
            formData,
            (responseText, request) => {
            // response
        });
    }

    createUUID() {
        var s = [];
        var hexDigits = "0123456789abcdef";
        for (var i = 0; i < 36; i++) {
            s[i] = hexDigits.substr(Math.floor(Math.random() * 0x10), 1);
        }
        s[14] = "4";  // bits 12-15 of the time_hi_and_version field to 0010
        s[19] = hexDigits.substr((s[19] & 0x3) | 0x8, 1);  // bits 6-7 of the clock_seq_hi_and_reserved to 01
        s[8] = s[13] = s[18] = s[23] = "-";

        var uuid = s.join("");
        return uuid;
    }
}
