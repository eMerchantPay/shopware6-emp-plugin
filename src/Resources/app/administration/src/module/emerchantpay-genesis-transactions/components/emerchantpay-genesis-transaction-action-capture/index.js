import template from './emerchantpay-genesis-transaction-action-capture.html.twig';
import {
    APPROVED,
    DECLINED
} from "../emerchantpay-genesis-transaction-actions/emerchantpay-genesis-transaction-action-constants";

Shopware.Component.register('emerchantpay-genesis-transaction-action-capture', {
    template,

    inject: [
        'EmerchantpayGenesisTransaction'
    ],

    mixins: [
        'notification'
    ],

    props: {
        actionTransaction: {
            type: Object,
            required: true
        }
    },

    computed: {
        modalMessage: function () {
            let translation = this.$tc('emerchantpay-genesis-transactions.transaction-actions.descriptions.capture');
            translation = translation.replace('%amount%', this.actionTransaction.amount);
            translation = translation.replace('%currency%', this.actionTransaction.currency);
            translation = translation.replace('%transaction_type%', this.actionTransaction.transaction_type);
            return translation;
        }
    },

    data() {
        return {
            isLoading: false
        };
    },

    methods: {
        capturePayment() {
            this.isLoading = true;
            this.EmerchantpayGenesisTransaction
                .doCapture(this.$route.id, this.actionTransaction.unique_id)
                .then((data) => {
                    if (data.status === 'success') {
                        this.parseResponse(data.response);
                    }

                    if (data.status === 'error') {
                        this.createNotificationError({
                            title: this.$tc('emerchantpay-genesis-transactions.notifications.error-title'),
                            message: this.$tc(
                                'emerchantpay-genesis-transactions.transaction-actions.messages.capture-failure'
                            ) + '</br>' + data.message
                        });
                    }

                    this.isLoading = false;
                    this.closeModal();

                    this.$emit('executed-capture-event');
                });
        },

        parseResponse(response) {
            if (response.status === APPROVED) {
                this.createNotificationSuccess({
                    title: this.getSuccessfulResponseTitle(response),
                    message: this.getSuccessfulResponseMessage(response)
                });
            }

            if (response.status !== APPROVED) {
                this.createNotificationInfo({
                    title: this.getSuccessfulResponseTitle(response),
                    message: this.getSuccessfulResponseMessage(response)
                });
            }
        },

        getSuccessfulResponseTitle(response) {
            let title = this.$tc('emerchantpay-genesis-transactions.notifications.success-title');

            if (!response.hasOwnProperty('status') || response.status !== APPROVED) {
                title = this.$tc('emerchantpay-genesis-transactions.notifications.error-title');
            }

            return title;
        },

        getSuccessfulResponseMessage(response) {
            let message = '';
            switch (response.status) {
                case APPROVED:
                    message = this.$tc(
                        'emerchantpay-genesis-transactions.transaction-actions.messages.capture-success'
                    ) + ` ${response.amount} ${response.currency}` +
                        '</br>' + ((response.message) ? response.message : '');
                    break;
                case DECLINED:
                    message = this.$tc(
                        'emerchantpay-genesis-transactions.transaction-actions.messages.capture-decline'
                    ) + '</br>' + ((response.message) ? response.message : '');
                    break;
            }

            return message;
        },

        closeModal() {
            this.$emit('modal-close');
        }
    }
});
