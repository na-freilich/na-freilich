import template from './sw-cms-el-order-promotion.html.twig';
import './sw-cms-el-order-promotion.scss';

Shopware.Component.register('sw-cms-el-order-promotion', {
    template,

    mixins: [
        'cms-element'
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('order-promotion');
        }
    }
});