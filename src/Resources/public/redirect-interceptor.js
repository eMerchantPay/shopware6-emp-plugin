document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('#confirmOrderForm');

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            validateCart().then(isValid => {
                if (!isValid && currentRoute === 'frontend.checkout.confirm.page') {
                    location.reload();
                } else {
                    const selectedPaymentMethodId = getSelectedPaymentMethodId();
                    if (selectedPaymentMethodId === paymentMethodId) {
                        const iframe = empCreateIframeElement();
                        form.setAttribute('target', 'emp-threeds-iframe');
                    } else {
                        form.removeAttribute('target');
                    }
                    form.submit();
                }
            });
        });
    }
});

function getSelectedPaymentMethodId() {
    const selectedPaymentMethod = document.querySelector('input[name="paymentMethodId"]:checked');

    return selectedPaymentMethod ? selectedPaymentMethod.value : null;
}

function empCreateIframeElement() {
    const div    = document.createElement('div');
    const header = document.createElement('div');
    const iframe = document.createElement('iframe');

    div.className    = 'emp-threeds-modal';
    header.className = 'emp-threeds-iframe-header';
    iframe.className = 'emp-threeds-iframe';

    iframe.setAttribute('name', 'emp-threeds-iframe');
    header.innerHTML = '<h3>The payment is being processed<br><span>Please, wait</span></h3>';
    iframe.onload    = function () {
        header.style.display = 'none';
        div.style.display    = 'block';
    }

    div.appendChild(header);
    div.appendChild(iframe);

    document.body.appendChild(div);

    div.style.display = 'block';

    return iframe;
}

function validateCart() {
    return fetch(cartValidationUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data     => data.valid);
}
