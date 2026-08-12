import template from './sw-cms-el-order-address.html.twig';
import './sw-cms-el-order-address.scss';

Shopware.Component.register('sw-cms-el-order-address', {
    template,

    mixins: [
        'cms-element'
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('order-address');
        }
    }
});