<?php declare(strict_types=1);
/**
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
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace Emerchantpay\Genesis\Utils\Data;

use Emerchantpay\Genesis\Utils\Data\Base\DataAdapter;

/**
 * Class ReferenceData
 * @package Emerchantpay\Genesis\Utils\Data
 *
 * @method string getTransactionId()     Reference Transaction Id
 * @method string getUsage()             Reference Transaction Usage
 * @method string getRemoteIp()          Reference Transaction Remote Ip
 * @method string getReferenceId()       Unique Id of the affected transaction
 * @method int    getAmount()            Reference Transaction Amount
 * @method string getCurrency()          Reference Transaction Currency
 * @method string getOrderId()           Reference Transaction Shopware OrderId
 * @method string getTerminalToken()     Reference Transaction Terminal Token
 * @method string getShopwarePaymentId() Shopware Order Payment Id
 *
 * @method $this setTransactionId($value)     Reference Transaction Id
 * @method $this setUsage($value)             Reference Transaction Usage
 * @method $this setRemoteIp($value)          Reference Transaction Remote Ip
 * @method $this setReferenceId($value)       Unique Id of the affected transaction
 * @method $this setAmount($value)            Reference Transaction Amount
 * @method $this setCurrency($value)          Reference Transaction Currency
 * @method $this setOrderId($value)           Reference Transaction Shopware OrderId
 * @method $this setTerminalToken($value)     Reference Transaction Terminal Token
 * @method $this setShopwarePaymentId($value) Shopware Order Payment Id
 */
class ReferenceData extends DataAdapter
{
    public function getFields()
    {
        return [
            'transaction_id',
            'usage',
            'remote_ip',
            'reference_id',
            'amount',
            'currency',
            'order_id',
            'terminal_token',
            'shopware_payment_id'
        ];
    }

    public static function invoke($data = [])
    {
        return new ReferenceData($data);
    }
}
