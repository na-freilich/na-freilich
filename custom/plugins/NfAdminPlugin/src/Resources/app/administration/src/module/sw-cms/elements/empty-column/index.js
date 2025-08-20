/**
 * @private
 * @package content
 */
Shopware.Component.register('sw-cms-el-empty-column', () => import('./component'));

/**
 * @private
 * @package content
 */
Shopware.Service('cmsService').registerCmsElement({
    name: 'empty-column',
    label: 'sw-cms.elements.emptyColumn.label',
    component: 'sw-cms-el-empty-column',
});