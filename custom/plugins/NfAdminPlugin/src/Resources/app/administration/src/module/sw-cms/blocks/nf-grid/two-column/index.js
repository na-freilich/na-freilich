/**
 * @private
 * @package content
 */
Shopware.Component.register('sw-cms-preview-two-column', () => import('./preview'));
/**
 * @private
 * @package content
 */
Shopware.Component.register('sw-cms-block-two-column', () => import('./component'));

/**
 * @private
 * @package content
 */
Shopware.Service('cmsService').registerCmsBlock({
    name: 'two-column',
    label: 'sw-cms.blocks.nfGrid.twoColumn.label',
    category: 'nf-grid',
    component: 'sw-cms-block-two-column',
    previewComponent: 'sw-cms-preview-two-column',
    defaultConfig: {
        marginBottom: '0px',
        marginTop: '0px',
        marginLeft: '0px',
        marginRight: '0px',
        sizingMode: 'boxed',
    },
    slots: {
        left: 'empty-column',
        right: 'empty-column',
    },
});