import './preview';
import './component';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'order-newsletter-block',
    label: 'nf-custom-finish-page.blocks.order-newsletter.label',
    category: 'finish-page',
    component: 'sw-cms-block-order-newsletter',
    previewComponent: 'sw-cms-preview-order-newsletter',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px'
    },
    slots: {
        newsletterContent: 'order-newsletter'
    }
});