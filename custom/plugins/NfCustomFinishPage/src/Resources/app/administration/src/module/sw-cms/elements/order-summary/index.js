import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'order-summary',
    label: 'sw-cms.elements.order-summary.label',
    component: 'sw-cms-el-order-summary',
    configComponent: 'sw-cms-el-config-order-summary',
    previewComponent: 'sw-cms-el-preview-order-summary',
    defaultConfig: {
    }
});