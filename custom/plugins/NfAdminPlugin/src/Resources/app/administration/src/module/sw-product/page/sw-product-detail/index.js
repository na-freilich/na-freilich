import template from './sw-product-detail.html.twig'

Shopware.Component.override('sw-product-detail', {
    template,

    methods: {
        toProductClick()
        {
            let domain = Shopware.Context.api.installationPath;
            let url = domain.concat('/detail/', this.productId);
            window.open(url, "_blank");
        }
    }
});