import template from './sw-cms-el-config-product-listing.html.twig'

Shopware.Component.override('sw-cms-el-config-product-listing', {
    template,

    methods: {
    },

    computed: {
        filterByNachlass: {
            get() {
                return this.isActiveFilter('nachlass-filter');
            },
            set(value) {
                this.updateFilters('nachlass-filter', value);
            },
        }
    }
});