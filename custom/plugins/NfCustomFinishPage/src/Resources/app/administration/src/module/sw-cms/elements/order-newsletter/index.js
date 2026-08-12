import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'order-newsletter',
    label: 'sw-cms.elements.order-newsletter.label',
    component: 'sw-cms-el-order-newsletter',
    configComponent: 'sw-cms-el-config-order-newsletter',
    previewComponent: 'sw-cms-el-preview-order-newsletter',
    defaultConfig: {
        title: {
            source: 'static',
             value: null
        },
        description: {
            source: 'static',
            value: null
        },
        usps: {
            source: 'static',
            value: []
        },
        inputLabel: {
            source: 'static',
            value: null
        },
        inputFooter: {
            source: 'static',
            value: null
        },
        buttonText: {
            source: 'static',
            value: null
        },
        checkboxLabel: {
            source: 'static',
            value: ''
        }
    }
});