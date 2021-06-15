import template from './emerchantpay-genesis-transaction-actions.html.twig'

Shopware.Component.register('emerchantpay-genesis-transaction-actions', {
    template,

    data() {
        return {
            modalType: ''
        };
    },

    props: {
        isVoidAvailable: {
            type: Boolean,
            required: true
        },
        isCaptureAvailable: {
            type: Boolean,
            required: true
        },
        isRefundAvailable: {
            type: Boolean,
            required: true
        },
        actionTransaction: {
            type: Object,
            required: true
        }
    },

    methods: {
        showModal(modalType) {
            this.modalType = modalType;
        },

        closeModal() {
            this.modalType = '';
        },

        referenceAction() {
            this.$emit('reference-action-event')
        }
    }
});
