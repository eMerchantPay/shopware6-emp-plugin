const ApiService = Shopware.Classes.ApiService;

class EmerchantpayGenesisTransaction extends ApiService
{
    constructor(httpClient, loginService, apiEndpoint = 'emerchantpay-v1') {
        super(httpClient, loginService, apiEndpoint)
    }

    getPaymentReferenceDetails(orderId, uniqueId) {
        const apiRoute = `${this.getApiBasePath()}/genesis/transaction/payment-reference-details`;

        return this.httpClient.post(
            apiRoute,
            {
                orderId: orderId,
                uniqueId: uniqueId
            },
            this.getDefaultOptions()
        ).then((response) => {
            return ApiService.handleResponse(response)
        })
    }

    doCapture(orderId, uniqueId) {
        const apiRoute = `${this.getApiBasePath('', '_action')}/genesis/transaction/capture`;

        return this.httpClient.post(
            apiRoute,
            {
                orderId: orderId,
                uniqueId: uniqueId
            },
            this.getDefaultOptions()
        ).then((response) => {
            return ApiService.handleResponse(response)
        })
    }

    doRefund(orderId, uniqueId) {
        const apiRoute = `${this.getApiBasePath('', '_action')}/genesis/transaction/refund`;

        return this.httpClient.post(
            apiRoute,
            {
                orderId: orderId,
                uniqueId: uniqueId
            },
            this.getDefaultOptions()
        ).then((response) => {
            return ApiService.handleResponse(response)
        })
    }

    doVoid(orderId, uniqueId) {
        const apiRoute = `${this.getApiBasePath('', '_action')}/genesis/transaction/void`;

        return this.httpClient.post(
            apiRoute,
            {
                orderId: orderId,
                uniqueId: uniqueId
            },
            this.getDefaultOptions()
        ).then((response) => {
            return ApiService.handleResponse(response)
        })
    }

    getDefaultOptions() {
        return {
            headers: this.getBasicHeaders(),
            version: Shopware.Context.api.apiVersion
        };
    }
}
export default EmerchantpayGenesisTransaction;
