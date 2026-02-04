import NfOrderDay from "./nf-order-day/nf-order-day.plugin";

// Register your plugin via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('nfOrderDayPlugin', NfOrderDay, '[data-nf-order-day]');