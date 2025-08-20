const ApiService = Shopware.Classes.ApiService;

export default class nfStatisticsVisitorsService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'nf-statistics') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'nfStatisticsVisitorsService';
    }

    getVisitorsList(page = 1, limit = 25, languageId = null, dateFrom = null, dateTo = null, searchTerm = "", sortBy = "layoutName", sortDirection = "ASC") {
        const headers = this.getBasicHeaders();
        const formData = new FormData();

        formData.append('page', page);
        formData.append('limit', limit);
        formData.append('languageId', languageId);
        formData.append('dateFrom', dateFrom);
        formData.append('dateTo', dateTo);
        formData.append('searchTerm', searchTerm);
        formData.append('sortBy', sortBy);
        formData.append('sortDirection', sortDirection);

        return this.httpClient.post(
            `/_action/${this.getApiBasePath()}/visitors`, formData,
            {
                params: {},
                headers
            }
        ).then((response) => {
            return ApiService.handleResponse(response)
        });
    }

    getConversionList(page = 1, limit = 25, languageId = null, dateFrom = null, dateTo = null, searchTerm = "", sortBy = "layoutName", sortDirection = "ASC") {
        const headers = this.getBasicHeaders();
        const formData = new FormData();

        formData.append('page', page);
        formData.append('limit', limit);
        formData.append('languageId', languageId);
        formData.append('dateFrom', dateFrom);
        formData.append('dateTo', dateTo);
        formData.append('sortBy', sortBy);
        formData.append('sortDirection', sortDirection);

        return this.httpClient.post(
            `/_action/${this.getApiBasePath()}/conversion`, formData,
            {
                params: {},
                headers
            }
        ).then((response) => {
            return ApiService.handleResponse(response)
        });
    }
}

Shopware.Service().register('nfStatisticsVisitorsService', () => {
    return new nfStatisticsVisitorsService(
        Shopware.Application.getContainer('init').httpClient,
        Shopware.Service('loginService')
    );
});