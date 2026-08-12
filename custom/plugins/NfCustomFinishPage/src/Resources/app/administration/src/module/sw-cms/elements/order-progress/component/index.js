import template from './sw-cms-el-order-progress.html.twig';
import './sw-cms-el-order-progress.scss';

Shopware.Component.register('sw-cms-el-order-progress', {
    template,

    mixins: [
        'cms-element'
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('order-progress');
        }
    }
});