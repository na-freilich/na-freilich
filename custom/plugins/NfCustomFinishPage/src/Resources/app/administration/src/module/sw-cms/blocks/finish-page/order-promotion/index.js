import './preview';
import './component';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'order-promotion-block',
    label: 'nf-custom-finish-page.blocks.order-promotion.label',
    category: 'finish-page',
    component: 'sw-cms-block-order-promotion',
    previewComponent: 'sw-cms-preview-order-promotion',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px'
    },
    slots: {
        promotionContent: 'order-promotion'
    }
});