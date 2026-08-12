import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'nf-two-column-booking',
    label: 'nf-booking.cms.label.twoColumnTitle',
    component: 'sw-cms-el-nf-two-column-booking',
    configComponent: 'sw-cms-el-config-nf-two-column-booking',
    previewComponent: 'sw-cms-el-preview-nf-two-column-booking',
    defaultConfig: {
        locationIdLeft: {
            source: 'static',
            value: null
        },
        locationIdRight: {
            source: 'static',
            value: null
        }
    }
});