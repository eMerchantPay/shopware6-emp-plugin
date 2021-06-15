import EmerchantpayGenesisUtils from '../services/emerchantpay-genesis-utils.service';

const { Application } = Shopware;
const initContainer = Application.getContainer('init');

Application.addServiceProvider(
    'EmerchantpayGenesisUtils',
    (container) =>
        new EmerchantpayGenesisUtils(initContainer.httpClient, container.loginService)
);
