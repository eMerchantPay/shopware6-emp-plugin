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

namespace Genesis\Api\Request\NonFinancial\Kyc\ClientVerification;

use Genesis\Api\Request\Base\NonFinancial\Kyc\BaseRequest as KYCBaseRequest;
use Genesis\Api\Traits\Request\Financial\ReferenceAttributes;
use Genesis\Utils\Common;

/**
 * Class Register
 *
 * Verification register request can be performed by reference_id.
 * A reference id registration allows you to store the reference id in Genesis
 * and receive notifications in Genesis for it.
 *
 * @package Genesis\Api\Request\NonFinancial\Kyc\ClientVerification
 *
 * @method $this setReferenceId($value)
 * @method string getReferenceId()
 */
class Register extends KYCBaseRequest
{
    use ReferenceAttributes;

    /**
     * Define Verifications Register endpoint
     */
    public function __construct()
    {
        parent::__construct('verifications/register');
    }

    /**
     * Set the required fields
     *
     * @return void
     */
    protected function setRequiredFields()
    {
        $requiredFields = [
            'reference_id',
        ];

        $this->requiredFields = Common::createArrayObject($requiredFields);
    }

    /**
     * @return array
     */
    protected function getRequestStructure()
    {
        return [
            'reference_id' => $this->reference_id,
        ];
    }
}
