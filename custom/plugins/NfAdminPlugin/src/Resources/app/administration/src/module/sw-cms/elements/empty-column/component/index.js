import template from './sw-cms-el-empty-column.html.twig';
import './sw-cms-el-empty-column.scss';

const { Mixin } = Shopware;

/**
 * @private
 * @package content
 */
export default {
    template,

    inject: [
        'cmsService'
    ],

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },

        cmsServiceState() {
            return this.cmsService.getCmsServiceState();
        },
    },


    mixins: [
        Mixin.getByName('cms-element'),
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('empty-column');
        },

        onClickSwap() {
            this.$parent.$parent.onElementClick();
        }
    },
};