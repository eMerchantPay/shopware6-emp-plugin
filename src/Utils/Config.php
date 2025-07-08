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

namespace Emerchantpay\Genesis\Utils;

use Emerchantpay\Genesis\Constants\Config as ConfigKeys;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class Config
{
    /**
     * @var SystemConfigService
     */
    private $shopwareSystemConfig;

    public function __construct(SystemConfigService $systemConfig)
    {
        $this->shopwareSystemConfig = $systemConfig;
    }

    /**
     * Retrieve all Emerchantpay Configs
     *
     * @return array|bool|float|int|string|null
     */
    public function getAllConfigs()
    {
        $defaultConfig = [];

        foreach (ConfigKeys::getAllCheckoutConstants() as $configKey) {
            $defaultConfig[$configKey] = null;
        }

        return array_merge($defaultConfig, $this->shopwareSystemConfig->get('EmerchantpayGenesis.config'));
    }

    /**
     * Get single Config Key's Value
     *
     * @param string $key
     *
     * @return array|bool|float|int|string|null
     */
    public function getConfigKey($key)
    {
        return $this->shopwareSystemConfig->get("EmerchantpayGenesis.config.$key");
    }

    /**
     * Get Checkout Method Configs
     */
    public function getCheckout(): array
    {
        $configs = $this->getAllConfigs();

        if (!is_array($configs)) {
            return [];
        }

        return array_filter($configs, function ($configKey) {
            return strpos($configKey, ConfigKeys::CHECKOUT_CONFIG_KEY) === 0;
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get Core Shopware Shop Name
     */
    public function getShopName(): string
    {
        return $this->shopwareSystemConfig->get('core.basicInformation.shopName');
    }
}
