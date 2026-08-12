import './preview';
import './component';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'order-upselling-timer-block',
    label: 'nf-custom-finish-page.blocks.order-upselling-timer.label',
    category: 'finish-page',
    component: 'sw-cms-block-order-upselling-timer',
    previewComponent: 'sw-cms-preview-order-upselling-timer',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px'
    },
    slots: {
        upsellingTimerContent: 'order-upselling-timer'
    }
});