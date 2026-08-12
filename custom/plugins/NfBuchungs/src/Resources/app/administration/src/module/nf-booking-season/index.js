import './page/nf-booking-season-list';
import './page/nf-booking-season-detail';

Shopware.Module.register('nf-booking-season', {
    type: 'plugin',
    name: 'BookingSeason',
    title: 'nf-booking.general.mainMenuItemGeneral',
    description: 'nf-booking.general.description',
    color: '#ff3d58',
    icon: 'regular-products',

    routes: {
        list: {
            component: 'nf-booking-season-list',
            path: 'list'
        },
        detail: {
            component: 'nf-booking-season-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'nf.booking.season.list'
            }
        },
        create: {
            component: 'nf-booking-season-detail',
            path: 'create',
            meta: {
                parentPath: 'nf.booking.season.list'
            }
        }
    },

    navigation: [
        {
            id: 'nf-booking-root',
            label: 'nf-booking.general.mainMenuItem',
            color: '#ff3d58',
            icon: 'default-object-books',
            position: 100,
            parent: 'sw-catalogue'
        },
        {
            id: 'nf-booking-season-list',
            parent: 'nf-booking-root',
            label: 'nf-booking.general.seasonItem',
            path: 'nf.booking.season.list',
            position: 10
        }
    ]
});