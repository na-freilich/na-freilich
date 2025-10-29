import template from './sw-meteor-page.html.twig';


Shopware.Component.override('sw-meteor-page', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    }

});
