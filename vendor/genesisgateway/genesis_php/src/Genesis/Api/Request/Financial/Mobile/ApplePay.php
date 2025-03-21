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
 * @copyright   Copyright (C) 2015-2025 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/MIT The MIT License
 */

namespace Genesis\Api\Request\Financial\Mobile;

use Genesis\Api\Constants\Transaction\Parameters\Mobile\ApplePay\PaymentTypes as ApplePaySubtypes;
use Genesis\Api\Traits\Request\AddressInfoAttributes;
use Genesis\Api\Traits\Request\DocumentAttributes;
use Genesis\Api\Traits\Request\Financial\BirthDateAttributes;
use Genesis\Api\Traits\Request\Financial\Business\BusinessAttributes;
use Genesis\Api\Traits\Request\Financial\Cards\Recurring\RecurringTypeAttributes;
use Genesis\Api\Traits\Request\Financial\CryptoAttributes;
use Genesis\Api\Traits\Request\Financial\DescriptorAttributes;
use Genesis\Api\Traits\Request\Financial\FundingAttributes;
use Genesis\Api\Traits\Request\Financial\PaymentAttributes;
use Genesis\Api\Traits\Request\Mobile\ApplePayAttributes;
use Genesis\Exceptions\InvalidArgument;
use Genesis\Utils\Common as CommonUtils;
use Genesis\Utils\Currency;

/**
 * Class ApplePay
 *
 * Apple pay Request
 *
 * @package Genesis\Api\Request\Financial\Mobile\ApplePay
 */
class ApplePay extends \Genesis\Api\Request\Base\Financial
{
    use AddressInfoAttributes;
    use ApplePayAttributes;
    use BirthDateAttributes;
    use BusinessAttributes;
    use CryptoAttributes;
    use DescriptorAttributes;
    use DocumentAttributes;
    use FundingAttributes;
    use PaymentAttributes;
    use RecurringTypeAttributes;

    /**
     * Sets ApplePay token
     *
     * @param string $token
     * @return $this
     * @throws InvalidArgument
     */
    public function setJsonToken($token)
    {
        $tokenAttributes = CommonUtils::decodeJsonString($token, true);
        array_walk_recursive($tokenAttributes, function ($attributeValue, $attributeKey) {
            $property = 'token_' . CommonUtils::pascalToSnakeCase($attributeKey);
            if (property_exists($this, $property)) {
                $this->{'set' . CommonUtils::snakeCaseToCamelCase($property)}($attributeValue);
            }
        });

        return $this;
    }

    /**
     * Returns the Request transaction type
     * @return string
     */
    protected function getTransactionType()
    {
        return \Genesis\Api\Constants\Transaction\Types::APPLE_PAY;
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
            'payment_subtype',
            'token_version',
            'token_data',
            'token_signature',
            'token_ephemeral_public_key',
            'token_public_key_hash',
            'token_transaction_id',
            'token_display_name',
            'token_network',
            'token_type',
            'token_transaction_identifier',
            'amount',
            'currency'
        ];

        $this->requiredFields = CommonUtils::createArrayObject($requiredFields);

        $requiredFieldValues = [
            'currency'        => Currency::getList(),
            'payment_subtype' => ApplePaySubtypes::getAllowedPaymentTypes()
        ];

        $this->requiredFieldValues = CommonUtils::createArrayObject($requiredFieldValues);
    }

    /**
     * Add document_id conditional validation if it is present
     *
     * @return void
     *
     * @throws InvalidArgument
     * @throws \Genesis\Exceptions\ErrorParameter
     * @throws \Genesis\Exceptions\InvalidClassMethod
     */
    protected function checkRequirements()
    {
        $requiredFieldsValuesConditional = $this->requiredRecurringInitialTypesFieldValuesConditional();

        if ($this->document_id) {
            $requiredFieldsValuesConditional = array_merge_recursive(
                $requiredFieldsValuesConditional,
                $this->getDocumentIdConditions()
            );
        }

        $this->requiredFieldValuesConditional = CommonUtils::createArrayObject($requiredFieldsValuesConditional);

        parent::checkRequirements();
    }

    /**
     * Return the required parameters keys which values could evaluate as empty
     * Example value:
     * array(
     *     'class_property' => 'request_structure_key'
     * )
     *
     * @return array
     */
    protected function allowedEmptyNotNullFields()
    {
        return array(
            'amount' => 'amount'
        );
    }

    /**
     * Return additional request attributes
     * @return array
     */
    protected function getPaymentTransactionStructure()
    {
        return [
            'usage'                     => $this->usage,
            'amount'                    => $this->transformAmount($this->amount, $this->currency),
            'currency'                  => $this->currency,
            'remote_ip'                 => $this->remote_ip,
            'payment_subtype'           => $this->payment_subtype,
            'payment_token'             => $this->getPaymentTokenStructure(),
            'customer_email'            => $this->customer_email,
            'customer_phone'            => $this->customer_phone,
            'birth_date'                => $this->getBirthDate(),
            'billing_address'           => $this->getBillingAddressParamsStructure(),
            'shipping_address'          => $this->getShippingAddressParamsStructure(),
            'document_id'               => $this->document_id,
            'crypto'                    => $this->crypto,
            'business_attributes'       => $this->getBusinessAttributesStructure(),
            'dynamic_descriptor_params' => $this->getDynamicDescriptorParamsStructure(),
            'recurring_type'            => $this->getRecurringType(),
            'funding'                   => $this->getFundingAttributesStructure()
        ];
    }
}
