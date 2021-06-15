import EmerchantpayGenesisTransaction from '../services/emerchantpay-genesis-transaction.service';

const { Application } = Shopware;
const initContainer = Application.getContainer('init');

Application.addServiceProvider(
    'EmerchantpayGenesisTransaction',
    (container) =>
    new EmerchantpayGenesisTransaction(initContainer.httpClient, container.loginService)
);
