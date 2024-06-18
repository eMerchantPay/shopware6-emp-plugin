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

namespace Genesis\Api\Request\Financial\Alternatives\Trustly;

use Genesis\Api\Constants\Transaction\Parameters\IFrameTargets;
use Genesis\Api\Traits\Request\AddressInfoAttributes;
use Genesis\Api\Traits\Request\Financial\AsyncAttributes;
use Genesis\Api\Traits\Request\Financial\BirthDateAttributes;
use Genesis\Api\Traits\Request\Financial\Business\BusinessAttributes;
use Genesis\Api\Traits\Request\Financial\PaymentAttributes;
use Genesis\Exceptions\InvalidArgument;

/**
 * Class Sale
 *
 * Trustly Sale Alternative payment method
 *
 * @package Genesis\Api\Request\Financial\Alternatives\Trustly
 *
 * @method string getReturnSuccessUrlTarget() URL target for successful payment in Trustly iFrame
 * @method string getUserId()                 Unique user identifier defined by merchant
 * @method $this  setUserId($value)           Unique user identifier defined by merchant
 */
class Sale extends \Genesis\Api\Request\Base\Financial
{
    use AddressInfoAttributes;
    use AsyncAttributes;
    use BirthDateAttributes;
    use BusinessAttributes;
    use PaymentAttributes;

    /**
     * URL target for successful payment in Trustly iFrame.
     *
     * @var string $return_success_url_target
     */
    protected $return_success_url_target;

    /**
     * Unique user identifier defined by merchant
     *
     * @var string $user_id
     */
    protected $user_id;

    /**
     * URL target for successful payment in Trustly iFrame.
     * Possible values: self, parent, top.
     *
     * @param string $value
     * @return $this
     * @throws InvalidArgument
     */
    public function setReturnSuccessUrlTarget($value)
    {
        if (empty($value)) {
            $this->return_success_url_target = $value;

            return $this;
        }

        return $this->allowedOptionsSetter(
            'return_success_url_target',
            IFrameTargets::getAll(),
            $value,
            'Invalid value given for Return Success URL Target'
        );
    }

    /**
     * Returns the Request transaction type
     * @return string
     */
    protected function getTransactionType()
    {
        return \Genesis\Api\Constants\Transaction\Types::TRUSTLY_SALE;
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
            'amount',
            'currency',
            'return_success_url',
            'return_failure_url',
            'customer_email',
            'billing_country'
        ];

        $this->requiredFields = \Genesis\Utils\Common::createArrayObject($requiredFields);

        $requiredFieldValues = [
            'billing_country' => [
                'AT', 'BE', 'CZ', 'DK', 'EE', 'FI', 'DE', 'LV', 'LT', 'NL', 'NO', 'PL',
                'SK', 'ES', 'SE', 'GB'
            ],
            'currency'        => \Genesis\Utils\Currency::getList()
        ];

        $this->requiredFieldValues = \Genesis\Utils\Common::createArrayObject($requiredFieldValues);
    }

    /**
     * Return additional request attributes
     * @return array
     */
    protected function getPaymentTransactionStructure()
    {
        return array_merge(
            $this->getPaymentAttributesStructure(),
            [
                'return_success_url'        => $this->return_success_url,
                'return_failure_url'        => $this->return_failure_url,
                'return_success_url_target' => $this->return_success_url_target,
                'customer_email'            => $this->customer_email,
                'customer_phone'            => $this->customer_phone,
                'user_id'                   => $this->user_id,
                'birth_date'                => $this->getBirthDate(),
                'billing_address'           => $this->getBillingAddressParamsStructure(),
                'shipping_address'          => $this->getShippingAddressParamsStructure(),
                'business_attributes'       => $this->getBusinessAttributesStructure()
            ]
        );
    }
}
