import template from './sw-cms-el-order-summary.html.twig';
import './sw-cms-el-order-summary.scss';

Shopware.Component.register('sw-cms-el-order-summary', {
    template,

    mixins: [
        'cms-element'
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('order-summary');
        }
    }
});