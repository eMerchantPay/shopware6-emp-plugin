import template from './sw-order-detail.html.twig';

const Criteria = Shopware.Data.Criteria;

const emerchantpayHandlerIdentifier = 'handler_emerchantpay_checkoutpayment';

Shopware.Component.override('sw-order-detail', {
    template,

    data() {
        return {
            isEmerchantpayPayment: false
        };
    },

    watch: {
        orderId: {
            deep: true,
            handler() {
                if (!this.orderId) {
                    // Do not change isEmerchantpayPayment value
                    return;
                }

                const orderRepository = this.repositoryFactory.create('order');
                const orderCriteria = new Criteria(1, 1);
                orderCriteria.addAssociation('transactions');
                orderCriteria.getAssociation('transactions').addSorting(Criteria.sort('createdAt'));

                orderRepository.get(this.orderId, Shopware.Context.api, orderCriteria).then((order) => {
                    const transactionsQuantity = order.transactions.length;
                    const lastTransactionIndex = transactionsQuantity - 1;
                    if (transactionsQuantity <= 0 || !order.transactions[lastTransactionIndex].paymentMethodId
                    ) {
                        // Do not change isEmerchantpayPayment value
                        return;
                    }

                    const paymentMethodId = order.transactions[lastTransactionIndex].paymentMethodId;

                    if (paymentMethodId !== undefined && paymentMethodId !== null) {
                        this.setIsEmerchantpayPayment(paymentMethodId);
                    }
                });
            },
            immediate: true
        }
    },

    methods: {
        setIsEmerchantpayPayment(paymentMethodId) {
            const paymentMethodRepository = this.repositoryFactory.create('payment_method');
            paymentMethodRepository.get(paymentMethodId, Shopware.Context.api).then((paymentMethod) => {
                this.isEmerchantpayPayment = paymentMethod.formattedHandlerIdentifier === emerchantpayHandlerIdentifier;
            });
        }
    }
});
