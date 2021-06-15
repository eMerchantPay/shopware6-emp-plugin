<?php declare(strict_types=1);
/*
 * Copyright (C) 2021 emerchantpay Ltd.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      emerchantpay
 * @copyright   2021 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace Emerchantpay\Genesis\Utils\Mappers;

use Emerchantpay\Genesis\Utils\Data\ReferenceData as ReferenceDataModel;

/**
 * Class ReferenceData
 * @package Emerchantpay\Genesis\Utils\Mappers
 */
class ReferenceData
{
    /**
     * @var ReferenceDataModel
     */
    private $referenceData;

    public function __construct(ReferenceDataModel $referenceData)
    {
        $this->referenceData = $referenceData;
    }

    public function loadFromArray(array $rawData): void
    {
        $this->referenceData
            ->setTransactionId($rawData['transaction_id'])
            ->setRemoteIp($rawData['remote_ip'])
            ->setUsage($rawData['usage'])
            ->setReferenceId($rawData['reference_id'])
            ->setAmount($rawData['amount'])
            ->setCurrency($rawData['currency'])
            ->setOrderId($rawData['order_id'])
            ->setTerminalToken($rawData['terminal_token'])
            ->setShopwarePaymentId($rawData['shopware_payment_id']);
    }

    public function getReferenceData(): ReferenceDataModel
    {
        return $this->referenceData;
    }
}
