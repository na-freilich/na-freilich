// Import admin module
import './module/nf-booking-season';
import './module/nf-booking-location';
import './module/nf-booking';
import './module/nf-booking-discount';

// cms
import './module/sw-cms/elements/nf-two-column-booking';
import './module/sw-cms/blocks/booking/nf-two-column-booking';

//order
import './module/sw-order';

//customer
import './module/sw-customer';

// snippet

import deDE from './app/snippet/de-DE.json';
import enGB from './app/snippet/en-GB.json';

Shopware.Locale.extend('de-DE', deDE);
Shopware.Locale.extend('en-GB', enGB);
