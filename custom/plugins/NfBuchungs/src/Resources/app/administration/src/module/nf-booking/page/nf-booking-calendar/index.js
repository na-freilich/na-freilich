import template from './nf-booking-calendar.html.twig';
import './nf-booking-calendar.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('nf-booking-calendar', {
    template,

    inject: ['repositoryFactory'],

    data() {
        return {
            currentDateString: new Date().toISOString().split('T')[0],
            bookings: [],
            locations: [],
            isLoading: false,
            currentDate: new Date(),
            selectedSlot: null
        };
    },

    created() {
        this.loadLocations();
    },

    methods: {
        onDateChange(newDate) {
            if (!newDate) return;
            this.currentDateString = new Date(newDate);
        },

        loadLocations() {
            this.isLoading = true;
            const repository = this.repositoryFactory.create('nf_booking_location');
            const criteria = new Criteria();

            criteria.addSorting(Criteria.sort('name', 'ASC', false));
            criteria.setLimit(2);

            repository.search(criteria, Shopware.Context.api).then((result) => {
                this.locations = result;
                this.isLoading = false;
            });
        },

        onSlotSelect(slot) {
            this.selectedSlot = slot;
        },

    }
});