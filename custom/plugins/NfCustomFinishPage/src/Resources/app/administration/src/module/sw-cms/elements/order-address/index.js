import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'order-address',
    label: 'sw-cms.elements.order-address.label',
    component: 'sw-cms-el-order-address',
    configComponent: 'sw-cms-el-config-order-address',
    previewComponent: 'sw-cms-el-preview-order-address',
    defaultConfig: {
    }
});