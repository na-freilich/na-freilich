import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'nf-two-column-booking-block',
    label: 'nf-booking.cms.label.twoColumnTitle',
    category: 'nf-booking',
    component: 'sw-cms-block-nf-two-column-booking',
    previewComponent: 'sw-cms-preview-nf-two-column-booking',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        content: 'nf-two-column-booking'
    }
});