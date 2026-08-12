import template from './sw-cms-el-order-items.html.twig';
import './sw-cms-el-order-items.scss';

Shopware.Component.register('sw-cms-el-order-items', {
    template,

    mixins: [
        'cms-element'
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('order-items');
        }
    }
});