import Plugin from 'src/plugin-system/plugin.class';

export default class NfBookingTwoColumnPlugin extends Plugin {
    init() {
        this._datePicker = this.el.querySelector('[data-date-picker]');
        this._slots = this.el.querySelectorAll('[data-nf-booking-slot]');
        this._registerEvents();
    }

    _registerEvents() {
        this._datePicker.addEventListener('change', (event) => {
            const newDate = event.target.value;
            this._notifySlots(newDate);
            this._date = newDate;
        });

        window.addEventListener('nf-booking:refresh', this._refreshSlots.bind(this));
        window.addEventListener('nf-booking:initCartDate', this._refreshDate.bind(this));
    }

    _refreshDate(event) {
        const date = event.detail.date;
        if (!this._date && date && this._datePicker) {
            if(this._datePicker._flatpickr)
            {
                this._datePicker._flatpickr.setDate(date);
            }
            else {
                this._datePicker.value = date;

            }
            const changeEvent = new Event('change', { bubbles: true });
            this._datePicker.dispatchEvent(changeEvent);
        }
    }

    _refreshSlots() {
        if(this._date)
            this._notifySlots(this._date);
    }

    _notifySlots(date) {
        this._slots.forEach(slotEl => {
            const slotPlugin = window.PluginManager.getPluginInstanceFromElement(slotEl, 'NfBookingSlotPlugin');
            if (slotPlugin) {
                slotPlugin.refreshSlots(date);
            }
        });
    }
}