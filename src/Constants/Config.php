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

namespace Emerchantpay\Genesis\Constants;

class Config
{
    // ====================
    // Checkout Config Keys
    // ====================
    public const CHECKOUT_CONFIG_KEY = 'checkout';
    public const CHECKOUT_LIVE_MODE = self::CHECKOUT_CONFIG_KEY . 'LiveMode';
    public const CHECKOUT_USERNAME = self::CHECKOUT_CONFIG_KEY . 'MerchantUsername';
    public const CHECKOUT_PASSWORD = self::CHECKOUT_CONFIG_KEY . 'MerchantPassword';
    public const CHECKOUT_TRANSACTION_TYPES = self::CHECKOUT_CONFIG_KEY . 'TransactionTypes';
    public const CHECKOUT_LANGUAGE = self::CHECKOUT_CONFIG_KEY . 'Language';

    /**
     * Nested Transaction Types suffix
     */
    public const PPRO_TRANSACTION_SUFFIX = '_ppro';

    /**
     * Google Pay Transaction Prefix and Types
     */
    public const GOOGLE_PAY_TRANSACTION_PREFIX     = 'google_pay_';
    public const GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE = 'authorize';
    public const GOOGLE_PAY_PAYMENT_TYPE_SALE      = 'sale';

    /**
     * Get All Checkout Method Config Keys
     */
    public static function getAllCheckoutConstants(): array
    {
        return [
            self::CHECKOUT_LIVE_MODE,
            self::CHECKOUT_USERNAME,
            self::CHECKOUT_PASSWORD,
            self::CHECKOUT_TRANSACTION_TYPES,
            self::CHECKOUT_LANGUAGE,
        ];
    }
}
