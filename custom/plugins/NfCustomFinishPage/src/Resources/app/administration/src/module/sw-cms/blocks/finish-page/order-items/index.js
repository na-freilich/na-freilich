import './preview';
import './component';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'order-items-block',
    label: 'nf-custom-finish-page.blocks.order-items.label',
    category: 'finish-page',
    component: 'sw-cms-block-order-items',
    previewComponent: 'sw-cms-preview-order-items',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px'
    },
    slots: {
        itemsContent: 'order-items'
    }
});