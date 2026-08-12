import './page/nf-booking-location-list';
// import './page/nf-booking-location-detail';

Shopware.Module.register('nf-booking-location', {
    type: 'plugin',
    name: 'BookingLocation',
    title: 'nf-booking.general.mainMenuItemGeneral',
    description: 'nf-booking.general.description',
    color: '#ff3d58',
    icon: 'regular-products',

    routes: {
        list: {
            component: 'nf-booking-location-list',
            path: 'list'
        },
        detail: {
            component: 'nf-booking-location-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'nf.booking.location.list'
            }
        },
        create: {
            component: 'nf-booking-location-detail',
            path: 'create',
            meta: {
                parentPath: 'nf.booking.location.list'
            }
        }
    },

    navigation: [
        {
            id: 'nf-booking-location-list',
            parent: 'nf-booking-root',
            label: 'nf-booking.general.locationItem',
            path: 'nf.booking.location.list',
            position: 10
        }
    ]
});