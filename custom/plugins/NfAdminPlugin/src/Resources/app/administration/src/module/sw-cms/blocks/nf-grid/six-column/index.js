/**
 * @private
 * @package content
 */
Shopware.Component.register('sw-cms-preview-six-column', () => import('./preview'));
/**
 * @private
 * @package content
 */
Shopware.Component.register('sw-cms-block-six-column', () => import('./component'));

/**
 * @private
 * @package content
 */
Shopware.Service('cmsService').registerCmsBlock({
    name: 'six-column',
    label: 'sw-cms.blocks.nfGrid.sixColumn.label',
    category: 'nf-grid',
    component: 'sw-cms-block-six-column',
    previewComponent: 'sw-cms-preview-six-column',
    defaultConfig: {
        marginBottom: null,
        marginTop: null,
        marginLeft: null,
        marginRight: null,
        sizingMode: 'boxed',
    },
    slots: {
        column1: 'empty-column',
        column2: 'empty-column',
        column3: 'empty-column',
        column4: 'empty-column',
        column5: 'empty-column',
        column6: 'empty-column'
    },
});