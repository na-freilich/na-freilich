import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

import './blocks/finish-page/order-address';
import './blocks/finish-page/order-items';
import './blocks/finish-page/order-summary';
import './blocks/finish-page/order-newsletter';
import './blocks/finish-page/order-progress';
import './blocks/finish-page/order-promotion';
import './blocks/finish-page/order-upselling-timer';
import './elements/order-address';
import './elements/order-items';
import './elements/order-summary';
import './elements/order-newsletter';
import './elements/order-progress';
import './elements/order-promotion';
import './elements/order-upselling-timer';

Shopware.Module.register('sw-cms-finish-page-extension', {

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routeMiddleware(next, currentRoute) {
        next();
    }
});
