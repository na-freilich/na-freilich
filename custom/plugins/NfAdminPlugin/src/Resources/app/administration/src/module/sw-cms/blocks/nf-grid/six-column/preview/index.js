import template from './sw-cms-preview-six-column.html.twig';
import './sw-cms-preview-six-column.scss';

/**
 * @private
 * @package content
 */
export default {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },
};