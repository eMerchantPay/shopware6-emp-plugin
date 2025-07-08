<?php declare(strict_types=1);
/*
 * Copyright (C) 2025 emerchantpay Ltd.
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
 * @copyright   2025 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace Emerchantpay\Genesis\Constants;

class Config
{
    // ====================
    // Checkout Config Keys
    // ====================
    public const CHECKOUT_CONFIG_KEY                  = 'checkout';
    public const CHECKOUT_LIVE_MODE                   = self::CHECKOUT_CONFIG_KEY . 'LiveMode';
    public const CHECKOUT_USERNAME                    = self::CHECKOUT_CONFIG_KEY . 'MerchantUsername';
    public const CHECKOUT_PASSWORD                    = self::CHECKOUT_CONFIG_KEY . 'MerchantPassword';
    public const CHECKOUT_TRANSACTION_TYPES           = self::CHECKOUT_CONFIG_KEY . 'TransactionTypes';
    public const CHECKOUT_LANGUAGE                    = self::CHECKOUT_CONFIG_KEY . 'Language';
    public const CHECKOUT_TOKENIZATION                = self::CHECKOUT_CONFIG_KEY . 'Tokenization';
    public const CHECKOUT_BANK_CODES                  = self::CHECKOUT_CONFIG_KEY . 'BankCodes';
    public const CHECKOUT_THREEDS_ALLOWED             = self::CHECKOUT_CONFIG_KEY . 'ThreedsAllowed';
    public const CHECKOUT_THREEDS_CHALLENGE_INDICATOR = self::CHECKOUT_CONFIG_KEY . 'ThreedsChallengeIndicator';
    public const CHECKOUT_SCA_EXEMPTION               = self::CHECKOUT_CONFIG_KEY . 'ScaExemption';
    public const CHECKOUT_SCA_EXEMPTION_AMOUNT        = self::CHECKOUT_CONFIG_KEY . 'ScaExemptionAmount';
    public const CHECKOUT_IFRAME_PROCESSING           = self::CHECKOUT_CONFIG_KEY . 'IframeProcessing';

    /**
     * Google Pay Transaction Prefix and Types
     */
    public const GOOGLE_PAY_TRANSACTION_PREFIX     = 'google_pay_';
    public const GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE = 'authorize';
    public const GOOGLE_PAY_PAYMENT_TYPE_SALE      = 'sale';

    /**
     * PayPal Transaction Prefix and Types
     */
    public const PAYPAL_TRANSACTION_PREFIX     = 'pay_pal_';
    public const PAYPAL_PAYMENT_TYPE_AUTHORIZE = 'authorize';
    public const PAYPAL_PAYMENT_TYPE_SALE      = 'sale';
    public const PAYPAL_PAYMENT_TYPE_EXPRESS   = 'express';

    /**
     * Apple Pay Transaction Prefix and Types
     */
    public const APPLE_PAY_TRANSACTION_PREFIX     = 'apple_pay_';
    public const APPLE_PAY_PAYMENT_TYPE_AUTHORIZE = 'authorize';
    public const APPLE_PAY_PAYMENT_TYPE_SALE      = 'sale';

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
            self::CHECKOUT_BANK_CODES,
            self::CHECKOUT_LANGUAGE,
            self::CHECKOUT_TOKENIZATION,
            self::CHECKOUT_THREEDS_ALLOWED,
            self::CHECKOUT_THREEDS_CHALLENGE_INDICATOR,
            self::CHECKOUT_SCA_EXEMPTION,
            self::CHECKOUT_SCA_EXEMPTION_AMOUNT,
            self::CHECKOUT_IFRAME_PROCESSING,
        ];
    }
}
