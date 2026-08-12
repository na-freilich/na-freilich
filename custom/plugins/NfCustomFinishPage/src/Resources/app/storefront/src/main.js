import OrderPromotionPlugin from './plugin/order-promotion.plugin';
import UpsellingTimerPlugin from './plugin/upselling-timer.plugin';

window.PluginManager.register('OrderPromotionPlugin', OrderPromotionPlugin, '[data-order-promotion-plugin]');

PluginManager.register('UpsellingTimerPlugin', UpsellingTimerPlugin, '[data-nf-upselling-timer]');