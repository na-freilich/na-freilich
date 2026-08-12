import template from './nf-booking-season-list.html.twig';

Shopware.Component.register('nf-booking-season-list', {
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
                { property: 'name', label: 'Name', inlineEdit: 'string', routerLink: 'nf.booking.season.detail' },
                { property: 'startDate', label: 'Start (MMDD)' },
                { property: 'endDate', label: 'End (MMDD)' },
                { property: 'active', label: 'Active', inlineEdit: 'boolean' }
            ];
        }
    },

    methods: {
        formatMyDate(value) {
            if (!value) return '';

            const strValue = String(value).padStart(4, '0');
            const monthIndex = parseInt(strValue.substring(0, 2), 10);
            const day = strValue.substring(2, 4);

            const months = [
                'Jan.', 'Feb.', 'März', 'Apr.', 'Mai', 'Juni',
                'Juli', 'Aug.', 'Sept.', 'Okt.', 'Nov.', 'Dez.'
            ];

            if (monthIndex < 1 || monthIndex > 12) return strValue;

            return `${day}. ${months[monthIndex - 1]}`;
        }
    },

    created() {
        this.repository = this.repositoryFactory.create('nf_booking_season');
        this.repository.search(new Shopware.Data.Criteria(), Shopware.Context.api).then((result) => {
            this.items = result;
        });
    }
});