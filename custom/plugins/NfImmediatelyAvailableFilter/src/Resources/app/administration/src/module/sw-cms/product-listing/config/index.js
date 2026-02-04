import template from './sw-cms-el-config-product-listing.html.twig'

Shopware.Component.override('sw-cms-el-config-product-listing', {
    template,

    methods: {
    },

    computed: {
        filterByImmediatelyAvailable: {
            get() {
                return this.isActiveFilter('immediately-available-filter');
            },
            set(value) {
                this.updateFilters('immediately-available-filter', value);
            },
        }
    }
});