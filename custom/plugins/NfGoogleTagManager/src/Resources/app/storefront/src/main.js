import NfGtmTrackingPlugin from './plugin/nf-gtm-tracking.plugin';
import NfGtmCheckoutPlugin from './plugin/nf-gtm-checkout.plugin';

// Register your plugin via the existing PluginManager
const PluginManager = window.PluginManager;

PluginManager.register('NfGtmTrackingPlugin', NfGtmTrackingPlugin, 'body');
PluginManager.register('NfGtmCheckoutPlugin', NfGtmCheckoutPlugin, '.js-nf-gtm-checkout-form');