import template from './sw-order-emerchantpay-genesis-transaction-details.html.twig';
import {
    WEB_PAYMENT
} from './emerchantpay-genesis-tranasction-constants';

const Criteria = Shopware.Data.Criteria;
const { Component, Mixin, Filter } = Shopware;

Shopware.Component.register('sw-order-emerchantpay-genesis-transaction-details', {
    inject: [
        'repositoryFactory',
        'EmerchantpayGenesisUtils'
    ],

    mixins: [
        Mixin.getByName('notification')
    ],

    template,

    data() {
        return {
            paymentData: {},
            transactions: {},
            transactionHistory: [],
            amount: null,
            isPaymentLoading: true,
            isHistoryLoading: true
        }
    },

    computed: {
        transactionRepository() {
            return this.repositoryFactory.create('emerchantpay_genesis_payment_entity');
        },

        transactionCriteria() {
            return new Criteria(1, 100)
                .addFilter(
                    Criteria.equals('order_id', this.$route.params.id)
                )
                .addSorting(
                    Criteria.sort('created_at', 'ASC')
                );
        },

        transactionColumns() {
            return [
                {
                    property: 'unique_id',
                    label: this.$tc('emerchantpay-genesis-transactions.payment-history.unique_id'),
                    rawData: true
                },
                {
                    property: 'reference_id',
                    label: this.$tc('emerchantpay-genesis-transactions.payment-history.reference_id'),
                    rawData: true
                },
                {
                    property: 'status',
                    label: this.$tc('emerchantpay-genesis-transactions.payment-history.status'),
                    rawData: true
                },
                {
                    property: 'transaction_type',
                    label: this.$tc('emerchantpay-genesis-transactions.payment-history.transaction_type'),
                    rawData: true
                },
                {
                    property: 'mode',
                    label: this.$tc('emerchantpay-genesis-transactions.payment-history.mode'),
                    rawData: true
                },
                {
                    property: 'amount',
                    label: this.$tc('emerchantpay-genesis-transactions.payment-history.amount'),
                    rawData: true
                },
                {
                    property: 'currency',
                    label: this.$tc('emerchantpay-genesis-transactions.payment-history.currency'),
                    rawData: true
                },
                {
                    property: 'created_at',
                    label: this.$tc('emerchantpay-genesis-transactions.payment-history.updated_at'),
                    rawData: true
                }
            ]
        },

        dateFilter() {
            return Filter.getByName('date');
        },
    },

    created() {
        this.buildData()
    },

    metaInfo() {
        return {
            title: this.$tc('emerchantpay-genesis-transactions.tab-title')
        };
    },

    methods: {
        buildData: function () {
            this.resetData();

            this.getPaymentData().then((transactions) => {
                if (transactions.length <= 0) {
                    this.showErrorMessage(
                        this.$tc('emerchantpay-genesis-transactions.notifications.errors.payment-not-found')
                    );

                    return;
                }

                this.paymentData = {
                    transaction_id: transactions[0].transaction_id,
                    unique_id: transactions[0].unique_id,
                    amount: transactions[0].amount,
                    currency: transactions[0].currency,
                    mode: transactions[0].mode,
                    created_at: this.formatDate(transactions[0].created_at)
                };

                transactions.forEach((transaction) => {
                    this.transactionHistory.push({
                        unique_id: transaction.unique_id,
                        reference_id: transaction.reference_id,
                        status: transaction.status,
                        transaction_type: transaction.transaction_type,
                        mode: transaction.mode,
                        amount: transaction.amount,
                        currency: transaction.currency,
                        created_at: this.formatDate(transaction.created_at),
                        updated_at: this.formatDate(transaction.updated_at)
                    });
                });

                // Partials not supported in initial version
                // TODO add partials
                this.convertAmountToExponent(this.paymentData.amount, this.paymentData.currency)
                    .then((result) => {
                        this.paymentData.amount = result.amount;

                        this.transactionHistory.forEach((transaction) => {
                            transaction.amount = result.amount;
                        });

                        this.isPaymentLoading = false;
                        this.isHistoryLoading = false;
                    });
            });
        },

        showSuccessMessage: function (message) {
            this.createNotificationSuccess({
                title: this.$tc('emerchantpay-genesis-transactions.notifications.success-title'),
                message: message
            });
        },

        showErrorMessage: function (message) {
            this.createNotificationError({
                title: this.$tc('emerchantpay-genesis-transactions.notifications.error-title'),
                message: message
            });
        },

        formatDate(dateTime) {
            return this.dateFilter(dateTime, {
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        convertAmountToExponent: function (amount, currency) {
            return this.EmerchantpayGenesisUtils.convertAmountToExponent(amount, currency).then((response) => {
                return response;
            })
        },

        getPaymentData: function () {
            return this.transactionRepository.search(this.transactionCriteria, Shopware.Context.api).then(result => {
                return result;
            })
        },

        resetData: function () {
            this.paymentData = {};
            this.transactions = {};
            this.transactionHistory = [];
            this.amount = null;
            this.isHistoryLoading = true;
        },

        reloadData() {
            this.buildData();
        }
    }
});
