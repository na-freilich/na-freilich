import template from './sw-dashboard-index.html.twig'

Shopware.Component.override('sw-dashboard-index', {
    template,

    inject: [
        'nfStatisticsVisitorsOnlineService'
    ],
    data() {
        return {
            visitorsOnline: 0,
        }
    },

    computed: {
        visitorsOnlineMessage() {
            return this.$tc('nf-statistics.dashboard.visitors-online', this.visitorsOnline);
        }

    },

    methods: {
        createdComponent() {
            this.$super('createdComponent');

            this.nfStatisticsVisitorsOnlineService.getVisitorsOnline().then((result) => {
                this.isLoading = false;
                this.visitorsOnline = result.count;
            })
        }
    }
});