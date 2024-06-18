<?php

/**
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author      emerchantpay
 * @copyright   Copyright (C) 2015-2024 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/MIT The MIT License
 */

namespace Genesis\Api\Request\Financial\Payout;

use Genesis\Api\Constants\Transaction\Types;
use Genesis\Api\Request\Base\Financial;
use Genesis\Api\Traits\Request\AddressInfoAttributes;
use Genesis\Api\Traits\Request\Financial\AsyncAttributes;
use Genesis\Api\Traits\Request\Financial\PaymentAttributes;
use Genesis\Utils\Common as CommonUtils;

/**
 * Class RussianMobilePayout
 *
 * Russian Mobile Payout Request
 *
 * @package Genesis\Api\Request\Financial\Payout\RussianMobilePayout
 */
class RussianMobilePayout extends Financial
{
    use AddressInfoAttributes;
    use AsyncAttributes;
    use PaymentAttributes;

    /**
     * @return string
     */
    protected function getTransactionType()
    {
        return Types::RUSSIAN_MOBILE_PAYOUT;
    }

    /**
     * Set the required fields
     *
     * @return void
     */
    protected function setRequiredFields()
    {
        $requiredFields = [
            'transaction_id',
            'return_success_url',
            'return_failure_url',
            'amount',
            'currency',
            'customer_phone',
            'billing_country'
        ];

        $this->requiredFields = CommonUtils::createArrayObject($requiredFields);

        $requiredFieldValues = [
            'currency'        => ['RUB'],
            'billing_country' => ['RU'],
        ];

        $this->requiredFieldValues = CommonUtils::createArrayObject($requiredFieldValues);
    }

    /**
     * Return additional request attributes
     *
     * @return array
     */
    protected function getPaymentTransactionStructure()
    {
        return [
            'return_success_url' => $this->return_success_url,
            'return_failure_url' => $this->return_failure_url,
            'amount'             => $this->transformAmount($this->amount, $this->currency),
            'currency'           => $this->currency,
            'customer_email'     => $this->customer_email,
            'customer_phone'     => $this->customer_phone,
            'billing_address'    => $this->getBillingAddressParamsStructure(),
            'shipping_address'   => $this->getShippingAddressParamsStructure()
        ];
    }
}
