import template from './sw-cms-el-config-order-newsletter.html.twig';

Shopware.Component.register('sw-cms-el-config-order-newsletter', {
    template,

    mixins: ['cms-element'],

    created() {
        this.createdComponent();
    },


    methods: {
        createdComponent() {
            this.initElementConfig('order-newsletter');

            if (this.element?.config?.title && !this.element.config.title.value) {
                this.element.config.title.value = this.$tc('sw-cms.elements.order-newsletter.title');
            }

            if (this.element?.config?.description && !this.element.config.description.value) {
                this.element.config.description.value = this.$tc('sw-cms.elements.order-newsletter.description');
            }

            if (!Array.isArray(this.element.config.usps.value)) {
                this.element.config.usps.value = [];
            }

            if (this.element?.config?.buttonText && !this.element.config.buttonText.value) {
                this.element.config.buttonText.value = this.$tc('sw-cms.elements.order-newsletter.btnTitle');
            }
        }
    }
});