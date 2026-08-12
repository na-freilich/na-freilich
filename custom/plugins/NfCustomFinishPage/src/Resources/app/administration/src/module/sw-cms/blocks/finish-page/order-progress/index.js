import './preview';
import './component';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'order-progress-block',
    label: 'nf-custom-finish-page.blocks.order-progress.label',
    category: 'finish-page',
    component: 'sw-cms-block-order-progress',
    previewComponent: 'sw-cms-preview-order-progress',
    defaultConfig: {
        currentStep: {
            source: 'static',
            value: 1
        }
    },
    slots: {
        progressContent: 'order-progress'
    }
});