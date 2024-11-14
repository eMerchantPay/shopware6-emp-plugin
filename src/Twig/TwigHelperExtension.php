<?php declare(strict_types=1);
/*
 * Copyright (C) 2024 emerchantpay Ltd.
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
 * @copyright   2024 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace Emerchantpay\Genesis\Twig;

use Emerchantpay\Genesis\Constants\Config as ConfigKeys;
use Emerchantpay\Genesis\Service\PaymentMethodService;
use Emerchantpay\Genesis\Utils\Config;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class GenesisIframeCheck
 *
 * Add custom twig function to check if iframe processing is enabled
 *
 * @package Emerchantpay\Genesis\Twig
 */
class TwigHelperExtension extends AbstractExtension
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var PaymentMethodService
     */
    private PaymentMethodService $paymentMethodService;
    /**
     * @param Config               $config
     * @param PaymentMethodService $paymentMethodService
     */
    public function __construct(Config $config, PaymentMethodService $paymentMethodService)
    {
        $this->config = $config;
        $this->paymentMethodService = $paymentMethodService;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('isEmpIframeEnabled', [$this, 'isIframeEnabled']),
            new TwigFunction('empPaymentMethodId', [$this, 'paymentMethodId']),
        ];
    }

    /**
     * Check settings if the iframe processing is enabled
     *
     * @return bool
     */
    public function isIframeEnabled(): bool
    {
        return (bool)$this->config->getCheckout()[ConfigKeys::CHECKOUT_IFRAME_PROCESSING];
    }

    /**
     * Retrieve and return our payment method's id string
     *
     * @return string|null
     */
    public function paymentMethodId(): ?string
    {
        return $this->paymentMethodService->getPaymentMethodId();
    }
}
