import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'order-items',
    label: 'sw-cms.elements.order-items.label',
    component: 'sw-cms-el-order-items',
    configComponent: 'sw-cms-el-config-order-items',
    previewComponent: 'sw-cms-el-preview-order-items',
    defaultConfig: {
    }
});