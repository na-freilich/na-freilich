import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'order-progress',
    label: 'sw-cms.elements.order-progress.label',
    component: 'sw-cms-el-order-progress',
    configComponent: 'sw-cms-el-config-order-progress',
    previewComponent: 'sw-cms-el-preview-order-progress',
    defaultConfig: {
    }
});