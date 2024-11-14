const empForm = {
    form: document.querySelector('#confirmOrderForm'),

    init() {
        if (this.form) {
            this.form.addEventListener('submit', this.handleSubmit.bind(this));
        }
    },

    handleSubmit(event) {
        const selectedPaymentMethodId = this.getSelectedPaymentMethodId();
        if (selectedPaymentMethodId === empPaymentMethodId) {
            event.preventDefault();
            this.validateCart().then(isValid => {
                if (!isValid && empCurrentRoute === 'frontend.checkout.confirm.page') {
                    location.reload();
                } else {
                    this.createIframeElement();
                    this.form.setAttribute('target', 'emp-threeds-iframe');
                    this.form.submit();
                }
            });
        }
    },

    getSelectedPaymentMethodId() {
        const selectedPaymentMethod = document.querySelector('input[name="paymentMethodId"]:checked');
        return selectedPaymentMethod ? selectedPaymentMethod.value : null;
    },

    createIframeElement() {
        const div    = document.createElement('div');
        const header = document.createElement('div');
        const iframe = document.createElement('iframe');

        div.className    = 'emp-threeds-modal';
        header.className = 'emp-threeds-iframe-header';
        iframe.className = 'emp-threeds-iframe';

        iframe.setAttribute('name', 'emp-threeds-iframe');
        header.innerHTML = '<h3>The payment is being processed<br><span>Please, wait</span></h3>';

        iframe.onload = () => {
            div.style.display    = 'block';
            header.style.display = 'none';
        };

        div.appendChild(header);
        div.appendChild(iframe);

        document.body.appendChild(div);

        div.style.display = 'block';
    },

    validateCart() {
        return fetch(empCartValidationUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => data.valid);
    }
};

document.addEventListener('DOMContentLoaded', function () {
    empForm.init();
});
