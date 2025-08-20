import nfVisitorsStatisticPlugin from './plugin/nf-visitors-statistic.plugin';

const PluginManager = window.PluginManager;
PluginManager.register('VisitorsStatistic', nfVisitorsStatisticPlugin, '[data-nf-visitors-statistic]');
