import template from './template.html.twig';

const { Component } = Shopware;

Component.override('sw-product-category-form', {
    template,

    computed: {
        productWithBadges() {
            if (!this.product) return null;

            if (!this.product.extensions) {
                this.$set(this.product, 'extensions', {});
            }

            if (!this.product.extensions.nfBadges) {
                this.$set(this.product.extensions, 'nfBadges', []);
            }

            return this.product;
        }
    }
});