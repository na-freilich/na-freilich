import template from './nf-booking-slot-info.html.twig';
import './nf-booking-slot-info.scss';

const { Filter } = Shopware;

Shopware.Component.register('nf-booking-slot-info', {
    template,

    mixins: [Shopware.Mixin.getByName('notification')],

    inject: [
        'loginService',
    ],

    props: {
        slotId: {
            type: String,
            required: false,
            default: null
        }
    },

    data() {
        return {
            booking: null,
            isLoading: false
        };
    },

    watch: {
        slotId: {
            immediate: true,
            handler(newVal) {
                if (newVal) {
                    this.loadBookingData(newVal);
                } else {
                    this.booking = null;
                }
            }
        }
    },

    computed: {
        dateFilter() {
            return Filter.getByName('date');
        }
    },

    methods: {
        loadBookingData(slotId) {
            this.isLoading = true;
            const httpClient = Shopware.Application.getContainer('init').httpClient;

            const postData = {
                slotId: slotId
            };

            const requestConfig = {
                headers: {
                    Authorization: `Bearer ${this.loginService.getToken()}`
                }
            };
            return httpClient.post('_action/nf-booking/booking', postData, requestConfig)
                .then((response) => {
                    this.booking  = response.data.booking;
                    this.isLoading = false;
                });

        },

        // formatDate(value) {
        //     if (!value) return '';
        //
        //     const dateFilter = Shopware.Filter.get('date');
        //     if (dateFilter) {
        //         return dateFilter(value, {
        //             day: '2-digit',
        //             month: '2-digit',
        //             year: 'numeric'
        //         });
        //     }
        //
        //     return new Date(value).toLocaleDateString('de-DE');
        // }
    }

});