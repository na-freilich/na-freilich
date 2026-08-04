class CustomerStatsService {
    constructor(httpClient, loginService) {
        this.httpClient = httpClient;
        this.loginService = loginService;
    }

    getTurnover(customerId) {
        const headers = {
            Authorization: `Bearer ${this.loginService.getToken()}`
        };
        return this.httpClient.get(`_action/customer-stats/turnover/${customerId}`, { headers })
            .then((response) => response.data);
    }
}
export default CustomerStatsService;