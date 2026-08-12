import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

export default class NfBookingSlotPlugin extends Plugin {
    static options = {
        locationId: null,
        productId: null,
        date: null,
        endpoint: '/nf-booking/slots'
    };

    init() {
        this._client = new HttpClient();
        this._registerEvents();
    }

    _registerEvents() {
        this.el.addEventListener('click', (event) => {

            const deleteIcon = event.target.closest('.slot-delete-icon');

            if (deleteIcon) {
                event.preventDefault();
                event.stopPropagation();
                const btn = deleteIcon.closest('.slot-button');
                if (btn) {
                    this._onDeleteClick(btn);
                }
                return;
            }

            const btn = event.target.closest('.slot-button');
            if (btn) {
                this._onSlotClick(btn);
            }
        });

        this.el.addEventListener('mouseover', (event) => {
            const btn = event.target.closest('.slot-button');
            if (btn) {
                this._toggleGroupHighlight(btn, true);
            }
        });

        this.el.addEventListener('mouseout', (event) => {
            const btn = event.target.closest('.slot-button');
            if (btn) {
                this._toggleGroupHighlight(btn, false);
            }
        });

        window.addEventListener('nf-booking-item:delete', this._onRemoveBookingItem.bind(this));

    }

    _onRemoveBookingItem(event) {
        const itemId = event.detail.itemId;
        const ids = itemId.split('|');

        for (const id of ids) {
            const cleanId = id.trim();
            const slotElement = this.el.querySelector(`[data-slot-id="${cleanId}"]`);

            if (slotElement) {
                this.refreshSlots(this.options.date);
                break;
            }
        }
    }

    _onDeleteClick(btn) {
        const productId = this.options.productId;

        const formData = new FormData();
        formData.append('productId', productId);
        formData.append('slotId', btn.dataset.slotId);
        formData.append('timeStart', btn.dataset.bookingStart);
        formData.append('timeEnd', btn.dataset.bookingEnd);

        this._client.post('/nf-booking/deleteSlot', formData, (response) => {

            try {
                const res = JSON.parse(response);

                if (res.success) {
                    this.refreshSlots(this.options.date);
                    const event = new CustomEvent('nf-booking-cart:refresh', {});
                    document.dispatchEvent(event);
                } else {
                    this.refreshSlots(this.options.date);
                    alert('Error: ' + res.message);
                }
            } catch (e) {
                this._restoreButton(btn, originalHtml);
            }
        });
    }

    _cleanupTooltips() {
        document.querySelectorAll('.tooltip').forEach(tip => {
            const trigger = document.querySelector(`[aria-describedby="${tip.id}"]`);
            if (!trigger || !trigger.isConnected) {
                tip.remove();
            }
        });
    }
    _toggleGroupHighlight(btn, show) {
        this._cleanupTooltips();
        const groupData = btn.dataset.slotGroup;
        if (!groupData) return;

        try {
            const ids = JSON.parse(groupData);
            ids.forEach(id => {
                const target = this.el.querySelector(`[data-slot-group-id="${id}"]`);
                if (target) {
                    target.classList.toggle('is-highlighted', show);
                }
            });
        } catch (e) {
            console.error('Error parsing slot group', e);
        }
    }

    refreshSlots(date) {
        this._cleanupTooltips();
        this.options.date = date;
        const url =
            `${this.options.endpoint}?date=${date}&locationId=${this.options.locationId}&productId=${this.options.productId}`;
        this.el.innerHTML = '<div class="loader">...</div>';
        this._client.get(url, (response) => {
            this.el.innerHTML = response;
        });
    }

    _onSlotClick(btn) {
        const productId = this.options.productId;
        const locationId = this.options.locationId;
        const bookingDate = this.options.date;
        let bookingTimeStart = btn.dataset.bookingStart;
        let bookingTimeEnd = btn.dataset.bookingEnd;

        const groupData = btn.dataset.slotGroup;
        if (groupData) {
            const ids = JSON.parse(groupData);
            let minStart = bookingTimeStart;
            let maxEnd = bookingTimeEnd;
            ids.forEach(id => {
                const cleanId = id.toString().trim();
                const target = this.el.querySelector(`[data-slot-group-id="${cleanId}"]`);
                if (target) {
                    const s = target.dataset.bookingStart;
                    const e = target.dataset.bookingEnd;

                    if (s < minStart) minStart = s;
                    if (e > maxEnd) maxEnd = e;
                }
            });

            bookingTimeStart = minStart;
            bookingTimeEnd = maxEnd;
        }

        const originalHtml = btn.innerHTML;
        const isSelected = btn.dataset.status === 'selected';
        if (isSelected) {
            return;
        }

        btn.classList.add('disabled');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        const formData = new FormData();
        formData.append('productId', productId);
        formData.append('locationId', locationId);
        formData.append('date', bookingDate);
        formData.append('timeStart', bookingTimeStart);
        formData.append('timeEnd', bookingTimeEnd);

        this._client.post('/nf-booking/reserve', formData, (response) => {

            try {
                const res = JSON.parse(response);

                if (res.success) {
                    this.refreshSlots(bookingDate);
                    const event = new CustomEvent('nf-booking-cart:refresh', {});
                    document.dispatchEvent(event);
                } else {
                    // this._restoreButton(btn, originalHtml);
                    this.refreshSlots(bookingDate)
                    alert('Error: ' + res.message);
                }
            } catch (e) {
                this._restoreButton(btn, originalHtml);
            }

        });
    }
    _restoreButton(btn, html) {
        btn.classList.remove('disabled');
        btn.disabled = false;
        btn.innerHTML = html;
    }
}