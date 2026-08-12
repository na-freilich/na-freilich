import './component/nf-booking-location-slots';
import './component/nf-booking-slot-info';

import './page/nf-booking-calendar';

Shopware.Module.register('nf-booking', {
    type: 'plugin',
    name: 'Booking',
    title: 'nf-booking.general.mainMenuItemGeneral',
    color: '#ff3d58',
    icon: 'regular-shopping-basket',

    routes: {
        list: {
            component: 'nf-booking-calendar',
            path: 'calendar'
        }
    },
    navigation: [{
        id: 'nf-booking-calendar',
        path: 'nf.booking.list',
        label: 'nf-booking.general.calendarLabel',
        parent: 'nf-booking-root',
        position: 10,
        icon: 'regular-calendar'
    }]
});