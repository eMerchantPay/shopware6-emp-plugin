<?php
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

namespace Emerchantpay\Genesis\Service\Payment\Helpers;

use Emerchantpay\Genesis\Utils\Data\PaymentData;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\PasswordChangeIndicators;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\RegistrationIndicators;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\ShippingAddressUsageIndicators;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\UpdateIndicators;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\MerchantRisk\ReorderItemIndicators;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\MerchantRisk\ShippingIndicators;

/**
 * Class ThreedsIndicatorService
 */
class ThreedsIndicatorService
{
    const CURRENT_TRANSACTION_INDICATOR  = 'current_transaction';
    const LESS_THAN_30_DAYS_INDICATOR    = 'less_than_30_days';
    const MORE_30_LESS_60_DAYS_INDICATOR = 'more_30_less_60_days';
    const MORE_THAN_60_DAYS_INDICATOR    = 'more_than_60_days';

    /**
     * @var PaymentData
     */
    private $paymentData;

    /**
     * @var bool
     */
    private $isGuestCustomer;

    /**
     * @param $paymentData $paymentData
     * @return void
     */
    public function initializePaymentData($paymentData, $isGuestCustomer)
    {
        $this->paymentData     = $paymentData;
        $this->isGuestCustomer = $isGuestCustomer;
    }

    /**
     * Fetch the Shipping Indicator from the Order Data
     *
     * @return string
     * @throws \Exception
     */
    public function fetchShippingIndicator()
    {
        $indicator = ShippingIndicators::OTHER;

        if (!$this->paymentData->hasPhysicalItems()) {
            $indicator = ShippingIndicators::DIGITAL_GOODS;
        }

        $hasAddressInfo = $this->paymentData->getBillingAddress() !== null &&
            $this->paymentData->getShippingAddress() !== null;

        if ($hasAddressInfo && $this->isSameAddressData()) {
            $indicator = ShippingIndicators::SAME_AS_BILLING;
        }

        if (!$this->isGuestCustomer && $hasAddressInfo && $indicator !== ShippingIndicators::SAME_AS_BILLING) {
            $indicator = ShippingIndicators::STORED_ADDRESS;
        }

        return $indicator;
    }

    /**
     * Fetch whether product/s have been previously re-ordered
     *
     * @param $customerOrders
     *
     * @return string
     * @throws \Exception
     */
    public function fetchReorderItemsIndicator($customerOrders)
    {
        if ($this->isGuestCustomer) {
            return ReorderItemIndicators::FIRST_TIME;
        }

        $orderedItems = $this->paymentData->getOrderItems();

        foreach ($customerOrders->getElements() as $order) {
            $filtered = array_filter($order->getLineItems()->getElements(), function ($item) use ($orderedItems) {
                return in_array($item->getProductId(), array_column($orderedItems, 'product_id'));
            });

            if (!empty($filtered)) {
                return ReorderItemIndicators::REORDERED;
            }
        }

        return ReorderItemIndicators::FIRST_TIME;
    }

    /**
     * Fetch 3DSv2 Account Holder Update Indicator
     *
     * @param \DateTime $customerModifyDate
     * @return string
     * @throws \Exception
     */
    public function fetchUpdateIndicator($customerModifyDate)
    {
        if (!$customerModifyDate || $this->isGuestCustomer) {
            return UpdateIndicators::CURRENT_TRANSACTION;
        }

        $today = new \DateTime();

        switch ($this->fetchIndicator($customerModifyDate, $today)) {
            case static::LESS_THAN_30_DAYS_INDICATOR:
                return UpdateIndicators::LESS_THAN_30DAYS;
            case static::MORE_30_LESS_60_DAYS_INDICATOR:
                return UpdateIndicators::FROM_30_TO_60_DAYS;
            case static::MORE_THAN_60_DAYS_INDICATOR:
                return UpdateIndicators::MORE_THAN_60DAYS;
            default:
                return UpdateIndicators::CURRENT_TRANSACTION;
        }
    }

    /**
     * Fetch 3DSv2 Account Holder Password Change Indicator
     *
     * @param string $customerUpdatedAt
     * @return string
     * @throws \Exception
     */
    public function fetchPasswordChangeIndicator($customerUpdatedAt)
    {
        $today     = new \DateTime();

        if ($customerUpdatedAt === null) {
            return PasswordChangeIndicators::NO_CHANGE;
        }

        $indicator = $this->fetchIndicator(
            \DateTime::createFromFormat(ThreedsService::DATE_TIME, $customerUpdatedAt),
            $today
        );

        switch ($indicator) {
            case static::LESS_THAN_30_DAYS_INDICATOR:
                return PasswordChangeIndicators::LESS_THAN_30DAYS;
            case static::MORE_30_LESS_60_DAYS_INDICATOR:
                return PasswordChangeIndicators::FROM_30_TO_60_DAYS;
            case static::MORE_THAN_60_DAYS_INDICATOR:
                return PasswordChangeIndicators::MORE_THAN_60DAYS;
            default:
                return PasswordChangeIndicators::DURING_TRANSACTION;
        }
    }

    /**
     * Fetch 3DSv2 Shipping Usage Indicator
     *
     * @param \DateTime $firstUsedDate
     * @return string
     * @throws \Exception
     */
    public function fetchShippingUsageIndicator($firstUsedDate)
    {
        switch ($this->fetchIndicator($firstUsedDate, new \DateTime())) {
            case static::LESS_THAN_30_DAYS_INDICATOR:
                return ShippingAddressUsageIndicators::LESS_THAN_30DAYS;
            case static::MORE_30_LESS_60_DAYS_INDICATOR:
                return ShippingAddressUsageIndicators::FROM_30_TO_60_DAYS;
            case static::MORE_THAN_60_DAYS_INDICATOR:
                return ShippingAddressUsageIndicators::MORE_THAN_60DAYS;
            default:
                return ShippingAddressUsageIndicators::CURRENT_TRANSACTION;
        }
    }

    /**
     * Fetch 3DSv2 Registration Indicator
     *
     * @param \DateTimeInterface $firsOrderDate
     * @return string
     * @throws \Exception
     */
    public function fetchRegistrationIndicator($firsOrderDate)
    {
        if ($this->isGuestCustomer) {
            return RegistrationIndicators::GUEST_CHECKOUT;
        }

        switch ($this->fetchIndicator($firsOrderDate, new \DateTime())) {
            case static::LESS_THAN_30_DAYS_INDICATOR:
                return RegistrationIndicators::LESS_THAN_30DAYS;
            case static::MORE_30_LESS_60_DAYS_INDICATOR:
                return RegistrationIndicators::FROM_30_TO_60_DAYS;
            case static::MORE_THAN_60_DAYS_INDICATOR:
                return RegistrationIndicators::MORE_THAN_60DAYS;
            default:
                return RegistrationIndicators::CURRENT_TRANSACTION;
        }
    }

    /**
     * Compare billing and shipping addresses
     *
     * @return bool
     */
    protected function isSameAddressData()
    {
        $billing = [
            $this->paymentData->getBillingFirstName(),
            $this->paymentData->getBillingLastName(),
            $this->paymentData->getBillingAddress(),
            $this->paymentData->getBillingZipcode(),
            $this->paymentData->getBillingCity(),
            $this->paymentData->getShippingState(),
            $this->paymentData->getBillingCountry()
        ];

        $shipping = [
            $this->paymentData->getShippingFirstName(),
            $this->paymentData->getShippingLastName(),
            $this->paymentData->getShippingAddress(),
            $this->paymentData->getShippingZipcode(),
            $this->paymentData->getShippingCity(),
            $this->paymentData->getShippingState(),
            $this->paymentData->getShippingCountry()
        ];

        return count(array_diff($billing, $shipping)) === 0;
    }

    /**
     * Return a date comparison string value that will be mapped to every indicator
     *
     * @param \DateTime|\DateTimeInterface $date
     * @param \DateTime $compareWith
     * @return mixed
     */
    protected function fetchIndicator($date, $compareWith)
    {
        $indicator      = static::CURRENT_TRANSACTION_INDICATOR;
        $updateInterval = $date->diff($compareWith);

        if (0 < $updateInterval->days && $updateInterval->days < 30) {
            $indicator = static::LESS_THAN_30_DAYS_INDICATOR;
        }

        if (30 <= $updateInterval->days && $updateInterval->days <= 60) {
            $indicator = static::MORE_30_LESS_60_DAYS_INDICATOR;
        }

        if ($updateInterval->days > 60) {
            $indicator = static::MORE_THAN_60_DAYS_INDICATOR;
        }

        return $indicator;
    }
}
