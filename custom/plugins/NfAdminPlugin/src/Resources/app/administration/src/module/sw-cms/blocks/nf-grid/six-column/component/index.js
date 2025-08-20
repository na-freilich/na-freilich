import template from './sw-cms-block-six-column.html.twig';
import './sw-cms-block-six-column.scss';

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