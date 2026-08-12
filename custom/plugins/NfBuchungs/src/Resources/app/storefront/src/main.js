import NfBookingTwoColumnPlugin from "./plugin/booking-two-column.plugin";
import NfBookingSlotPlugin from './plugin/booking-slot.plugin';
import NfBookingCartPlugin from './plugin/booking-cart.plugin';
import NfBookingTimerPlugin from './plugin/booking-timer.plugin';
import NfBookingCredit from './plugin/booking-credit.plugin';
import NfBookingCancelPlugin from './plugin/booking-cancel.plugin';

// Register your plugin via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('NfBookingTwoColumnPlugin', NfBookingTwoColumnPlugin, '[data-nf-booking-two-column]');
PluginManager.register('NfBookingSlotPlugin', NfBookingSlotPlugin, '[data-nf-booking-slot]');
PluginManager.register('NfBookingCartPlugin', NfBookingCartPlugin, '[data-nf-booking-cart]');
PluginManager.register('NfBookingTimerPlugin', NfBookingTimerPlugin, '[data-nf-booking-timer]');
PluginManager.register('NfBookingCredit', NfBookingCredit, '[data-nf-booking-credit]');
PluginManager.register('NfBookingCancelPlugin', NfBookingCancelPlugin, '.js-booking-cancel-form');