import './page/nf-booking-discount-list';
import './page/nf-booking-discount-detail';

Shopware.Module.register('nf-booking-discount', {
    type: 'plugin',
    name: 'BookingDiscount',
    title: 'nf-booking.discount.mainMenuItemGeneral',
    description: 'nf-booking.discount.descriptionTextModule',
    color: '#ff3d58',
    icon: 'regular-gift',

    routes: {
        list: {
            component: 'nf-booking-discount-list',
            path: 'list'
        },
        detail: {
            component: 'nf-booking-discount-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'nf.booking.discount.list'
            }
        },
        create: {
            component: 'nf-booking-discount-detail',
            path: 'create',
            meta: {
                parentPath: 'nf.booking.discount.list'
            }
        }
    },

    navigation: [{
        id: 'nf-booking-discount',
        label: 'nf-booking.discount.mainMenuItemGeneral',
        color: '#ff3d58',
        path: 'nf.booking.discount.list',
        icon: 'regular-gift',
        parent: 'nf-booking-root',
        position: 4
    }]
});