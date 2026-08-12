import './preview';
import './component';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'order-summary-block',
    label: 'nf-custom-finish-page.blocks.order-summary.label',
    category: 'finish-page',
    component: 'sw-cms-block-order-summary',
    previewComponent: 'sw-cms-preview-order-summary',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px'
    },
    slots: {
        summaryContent: 'order-summary'
    }
});