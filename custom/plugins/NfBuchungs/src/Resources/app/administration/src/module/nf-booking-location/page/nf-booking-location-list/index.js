import template from './nf-booking-location-list.html.twig';

Shopware.Component.register('nf-booking-location-list', {
    template,

    inject: ['repositoryFactory'],

    data() {
        return {
            repository: null,
            items: null
        };
    },

    computed: {
        columns() {
            return [
                { property: 'name', label: 'Name', inlineEdit: 'string', routerLink: 'nf.booking.location.detail' },
                { property: 'active', label: 'Active', inlineEdit: 'boolean' }
            ];
        }
    },

    methods: {

    },

    created() {
        this.repository = this.repositoryFactory.create('nf_booking_location');
        this.repository.search(new Shopware.Data.Criteria(), Shopware.Context.api).then((result) => {
            this.items = result;
        });
    }
});