import './extension/sw-order-detail/index';
import './page/sw-order-emerchantpay-genesis-transaction-details';
import './components/index';

Shopware.Module.register('sw-tab-emerchantpay-genesis-transaction-details', {
    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.order.detail') {
            currentRoute.children.push({
                name: 'sw.order.emerchantpay-genesis-transaction-details',
                path: '/sw/order/emerchantpay-genesis-transaction/detail/:id',
                isChildren: true,
                component: 'sw-order-emerchantpay-genesis-transaction-details',
                meta: {
                    parentPath: "sw.order.index"
                }
            });
        }
        next(currentRoute);
    }
});
