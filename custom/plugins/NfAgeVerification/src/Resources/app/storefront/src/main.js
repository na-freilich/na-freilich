import AgeVerificationPlugin from './plugin/age-verification.plugin';

const PluginManager = window.PluginManager;

PluginManager.register('AgeVerificationPlugin', AgeVerificationPlugin, '[data-age-verification]');