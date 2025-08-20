import template from './sw-cms-block-four-column.html.twig';
import './sw-cms-block-four-column.scss';

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