const ApiService = Shopware.Classes.ApiService;

export default class nfStatisticsVisitorsOnlineService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'nf-statistics') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'nfStatisticsVisitorsOnlineService';
    }

    getVisitorsOnline() {
        const headers = this.getBasicHeaders();
        const formData = new FormData();

        return this.httpClient.post(
            `/_action/${this.getApiBasePath()}/get-visitors-online`, formData,
            {
                params: {},
                headers
            }
        ).then((response) => {
            return ApiService.handleResponse(response)
        });
    }
}

Shopware.Service().register('nfStatisticsVisitorsOnlineService', () => {
    return new nfStatisticsVisitorsOnlineService(
        Shopware.Application.getContainer('init').httpClient,
        Shopware.Service('loginService')
    );
});