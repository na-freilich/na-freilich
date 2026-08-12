import template from './sw-cms-el-order-newsletter.html.twig';
import './sw-cms-el-order-newsletter.scss';

Shopware.Component.register('sw-cms-el-order-newsletter', {
    template,

    mixins: [
        'cms-element'
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('order-newsletter');
        }
    }
});