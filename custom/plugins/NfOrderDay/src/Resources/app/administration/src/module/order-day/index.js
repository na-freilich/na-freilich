// <plugin root>/src/Resources/app/administration/src/module/swag-example/index.js
import './page/order-day-list';
import './page/order-day-detail';
import './page/order-day-create';

import deDE from './snippet/de-DE';
import enGB from './snippet/en-GB';

Shopware.Module.register('order-day', {
    type: 'plugin',
    name: 'NfOrderDay',
    title: 'nf-order-day.general.mainMenuItemGeneral',
    description: 'nf-order-day.general.descriptionTextModule',
    color: '#ff3d58',
    icon: 'default-shopping-paper-bag-product',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'order-day-list',
            path: 'list'
        },
        detail: {
            component: 'order-day-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'order.day.list'
            }
        },
        create: {
            component: 'order-day-create',
            path: 'create',
            meta: {
                parentPath: 'order.day.list'
            }
        }
    },

    navigation: [{
        label: 'nf-order-day.general.mainMenuItemGeneral',
        color: '#ff3d58',
        path: 'order.day.list',
        icon: 'default-shopping-paper-bag-product',
        position: 10,
        parent: 'sw-catalogue'
    }]
});
