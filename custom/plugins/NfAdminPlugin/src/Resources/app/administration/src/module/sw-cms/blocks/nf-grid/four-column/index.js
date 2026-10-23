/**
 * @private
 * @package content
 */
Shopware.Component.register('sw-cms-preview-four-column', () => import('./preview'));
/**
 * @private
 * @package content
 */
Shopware.Component.register('sw-cms-block-four-column', () => import('./component'));

/**
 * @private
 * @package content
 */
Shopware.Service('cmsService').registerCmsBlock({
    name: 'four-column',
    label: 'sw-cms.blocks.nfGrid.fourColumn.label',
    category: 'nf-grid',
    component: 'sw-cms-block-four-column',
    previewComponent: 'sw-cms-preview-four-column',
    defaultConfig: {
        marginBottom: null,
        marginTop: null,
        marginLeft: null,
        marginRight: null,
        sizingMode: 'boxed',
    },
    slots: {
        left: 'empty-column',
        'center-left': 'empty-column',
        'center-right': 'empty-column',
        right: 'empty-column',
    },
});