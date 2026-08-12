import template from './nf-booking-location-slots.html.twig';
import './nf-booking-location-slots.scss';

Shopware.Component.register('nf-booking-location-slots', {
    template,

    mixins: [Shopware.Mixin.getByName('notification')],

    inject: [
        'loginService',
    ],

    props: {
        date: {
            type: String,
            required: true
        },
        location: {
            type: Object,
            required: true
        },
        selectedSlot: {
            type: Object,
            required: false,
            default: null
        },
        activeBookingId: {
            type: String,
            required: false,
            default: null
        }
    },

    data() {
        return {
            slots: null,
            selectedSlotId: null,
            selectedBookingId: null,
        };
    },

    created() {
        this.loadSlots();
    },

    methods: {

        onSlotClick(slot) {
            if (slot.isAvailable)
                return;

            if (slot.status === 'blocked')
                return;

            this.selectedSlotId = null;
            this.selectedBookingId = null;

            this.$nextTick(() => {
                this.selectedSlotId = slot.slotId;
                this.selectedBookingId = slot.bookingId;
                this.$emit('slot-click', slot);
            });
        },

        toggleBlock(slot){
            slot.isLocalLoading = true;

            const isUnblocking = slot.status === 'blocked';
            const httpClient = Shopware.Application.getContainer('init').httpClient;

            const postData = {
                date: this.date,
                start: slot.start,
                end: slot.end,
                locationId: this.location.id
            };

            let url = '_action/nf-booking/slot/block';
            if (isUnblocking)
            {
                url = '_action/nf-booking/slot/unblock';
                postData.slotId = slot.slotId
            }


            const requestConfig = {
                headers: {
                    Authorization: `Bearer ${this.loginService.getToken()}`
                }
            };
            this.isLoading = true;
            httpClient.post(url, postData, requestConfig)
                .then((response) => {
                    this.$emit('refresh-slots');

                    if(isUnblocking)
                    {
                        slot.isAvailable = true;
                        slot.status = 'available';
                    }
                    else
                        slot.status = 'blocked';

                    this.loadSlots();
                })
                .finally(() => {
                    slot.isLocalLoading = false;
                });
        },

        loadSlots() {
            this.isLoading = true;

            const httpClient = Shopware.Application.getContainer('init').httpClient;

            const postData = {
                locationId: this.location.id,
                date: this.date
            };

            const requestConfig = {
                headers: {
                    Authorization: `Bearer ${this.loginService.getToken()}`
                }
            };
            return httpClient.post('_action/nf-booking/slots', postData, requestConfig)
                .then((response) => {
                    this.slots = response.data.data;
                    this.isLoading = false;
                });
        }
    }
});