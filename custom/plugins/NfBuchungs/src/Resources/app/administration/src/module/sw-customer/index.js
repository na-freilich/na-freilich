import './page/sw-customer-detail';
import './view/nf-customer-detail-credits';

Shopware.Module.register('nf-booking-credit-extension', {
    type: 'plugin',

    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.customer.detail') {
            currentRoute.children.push({
                name: 'sw.customer.detail.booking-credits',
                path: 'booking-credits',
                component: 'nf-customer-detail-credits',
                meta: {
                    parentPath: 'sw.customer.index'
                }
            });
        }

        next();
    }
});