import CustomerStatsService from  './api/customer-stats.service';

Shopware.Application.addServiceProvider('customerStatsService', (container) => {
    const initContainer = Shopware.Application.getContainer('init');
    return new CustomerStatsService(initContainer.httpClient, container.loginService);
});