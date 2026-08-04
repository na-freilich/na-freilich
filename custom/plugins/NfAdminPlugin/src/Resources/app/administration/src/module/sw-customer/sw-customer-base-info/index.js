import template from './sw-customer-base-info.html.twig'

Shopware.Component.override('sw-customer-base-info', {
    inject: ['customerStatsService'],

    template,

    data() {
        return {
            turnoverData: null,
            isTurnoverLoading: false
        };
    },

    watch: {
        customer: {
            handler(newCustomer) {
                if (newCustomer && newCustomer.id) {
                    this.loadTurnoverData();
                }
            },
            immediate: true
        }
    },

    methods: {
        async loadTurnoverData() {
            if (this.isTurnoverLoading || !this.customer || !this.customer.id) {
                return;
            }

            this.isTurnoverLoading = true;

            try {
                const data = await this.customerStatsService.getTurnover(this.customer.id);
                this.turnoverData = data;
            } catch (exception) {
                console.error(exception);
            } finally {
                this.isTurnoverLoading = false;
            }
        },

        getMonthName(monthNumber) {
            const locale = Shopware.Store.get("session").currentLocale || 'en-GB';
            const date = new Date(2025, monthNumber - 1, 1);

            return new Intl.DateTimeFormat(locale, { month: 'long' }).format(date);
        }
    }
});