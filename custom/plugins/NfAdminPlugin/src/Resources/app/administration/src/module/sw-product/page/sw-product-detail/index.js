import template from './sw-product-detail.html.twig'

Shopware.Component.override('sw-product-detail', {
    template,

    data() {
        return {
            showToProductSalesChannelModal: false,
        };
    },

    methods: {
        toProductClick()
        {
            this.showToProductSalesChannelModal = true;
        },

        onCloseSaleschannelModal() {
            this.showToProductSalesChannelModal = false;
        },
    }
});