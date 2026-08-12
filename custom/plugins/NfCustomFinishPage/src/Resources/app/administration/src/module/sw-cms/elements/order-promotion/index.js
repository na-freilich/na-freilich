import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'order-promotion',
    label: 'sw-cms.elements.order-promotion.label',
    component: 'sw-cms-el-order-promotion',
    configComponent: 'sw-cms-el-config-order-promotion',
    previewComponent: 'sw-cms-el-preview-order-promotion',
    defaultConfig: {
        title: {
            source: 'static',
            value: 'Freunde empfehlen & 10 € sparen'
        },
        description: {
            source: 'static',
            value: 'Ihr persönlicher Empfehlungslink — Ihr Freund bekommt 5 € Rabatt, Sie 10 €.'
        },
        labelFriend: {
            source: 'static',
            value: 'Für Freund:'
        },
        labelOwner: {
            source: 'static',
            value: 'Für Sie:'
        }
    }
});