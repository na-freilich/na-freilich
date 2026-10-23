import template from './sw-cms-preview-four-column.html.twig';
import './sw-cms-preview-four-column.scss';

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