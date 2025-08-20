/**
 * @private
 * @package content
 */
Shopware.Component.register('sw-cms-preview-three-column', () => import('./preview'));
/**
 * @private
 * @package content
 */
Shopware.Component.register('sw-cms-block-three-column', () => import('./component'));

/**
 * @private
 * @package content
 */
Shopware.Service('cmsService').registerCmsBlock({
    name: 'three-column',
    label: 'sw-cms.blocks.nfGrid.threeColumn.label',
    category: 'nf-grid',
    component: 'sw-cms-block-three-column',
    previewComponent: 'sw-cms-preview-three-column',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed',
    },
    slots: {
        left: 'empty-column',
        center: 'empty-column',
        right: 'empty-column',
    },
});