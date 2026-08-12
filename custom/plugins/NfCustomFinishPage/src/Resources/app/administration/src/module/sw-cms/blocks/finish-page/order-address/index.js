import './preview';
import './component';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'order-address-block',
    label: 'nf-custom-finish-page.blocks.order-address.label',
    category: 'finish-page',
    component: 'sw-cms-block-order-address',
    previewComponent: 'sw-cms-preview-order-address',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px'
    },
    slots: {
        addressContent: 'order-address'
    }
});