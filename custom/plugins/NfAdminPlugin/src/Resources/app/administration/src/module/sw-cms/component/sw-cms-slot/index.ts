import template from './sw-cms-slot.html.twig';
import './sw-cms-slot.scss';

Shopware.Component.override('sw-cms-slot', {
    template,

    data() {
        return {
            firstSelectElement: false,
        }
    },

    computed:{
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },

        getSelectElementTitle()
        {
            if (this.firstSelectElement)
                return this.$tc('sw-cms.detail.title.elementSelectModal');

            return this.$tc('sw-cms.detail.title.elementChangeModal');
        },

        getSelectElementIcon()
        {
            if (this.firstSelectElement)
                return 'regular-checkmark';

            return 'regular-repeat';
        }
    },

    methods:{
        onElementClick()
        {
            this.firstSelectElement = true;
            // this.showElementSelection = true;
            this.$super('onElementButtonClick');
        },

        onCloseSettingsModal() {
            this.firstSelectElement = false;
            this.$super('onCloseSettingsModal');
        },
    }

});
