import template from './sw-page.html.twig';


Shopware.Component.override('sw-page', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    }

});
