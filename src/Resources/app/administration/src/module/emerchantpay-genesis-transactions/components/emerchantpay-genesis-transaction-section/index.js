import template from './emerchantpay-genesis-transaction-section.html.twig'

Shopware.Component.register('emerchantpay-genesis-transaction-section', {
    inject: [
        'EmerchantpayGenesisTransaction'
    ],

    template,

    props: {
        paymentData: Object
    },

    data() {
        return {
            canVoid: false,
            canCapture: false,
            canRefund: false,
            isTransactionLoading: true,
            actionTransaction : null,
            initialTransaction: null,
            captureTransaction: null,
            refundTransaction: null,
            voidTransaction: null
        }
    },

    created() {
        this.buildData();
    },

    methods: {
        buildData: function () {
            this.resetData();

            this.EmerchantpayGenesisTransaction.getPaymentReferenceDetails(
                this.$route.params.id,
                this.paymentData.unique_id
            ).then((data) => {
                this.canVoid = data.canVoid;
                this.canCapture = data.canCapture;
                this.canRefund = data.canRefund;
                this.actionTransaction = data.actionTransaction;
                this.initialTransaction = ((data.initialTransaction.length === 0) ? null : data.initialTransaction);
                this.actionUniqueId = data.actionUniqueId;
                this.refundTransaction = ((data.refundTransaction.length === 0) ? null : data.refundTransaction);
                this.captureTransaction = ((data.captureTransaction.length === 0) ? null : data.captureTransaction);
                this.voidTransaction = ((data.voidTransaction.length === 0) ? null : data.voidTransaction);

                this.isTransactionLoading = false;
            });
        },

        resetData: function () {
            this.canVoid = false;
            this.canCapture = false;
            this.canRefund = false;
            this.isTransactionLoading = true;
            this.actionTransaction =  null;
            this.initialTransaction = null;
            this.captureTransaction = null;
            this.refundTransaction = null;
            this.voidTransaction = null;
        },

        executedReferenceAction: function () {
            this.buildData();
            this.$emit('reload-event');
        }
    }
});
