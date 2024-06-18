<?php
/*
 * Copyright (C) 2022 emerchantpay Ltd.
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
 * @copyright   2022 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace Emerchantpay\Genesis\Service\Payment\Helpers;

use Emerchantpay\Genesis\Service\Payment\Shopware;
use Emerchantpay\Genesis\Utils\Data\PaymentData;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

/**
 * Class ThreedsService
 */
class ThreedsService
{
    const DATE_TIME = 'Y-m-d';

    /**
     * @var Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
     */
    private $orderRepository;

    /**
     * @var PaymentData
     */
    private $paymentData;

    /**
     * @var EntityRepository
     */
    private $customerAddressRepository;

    /**
     * @var EntitySearchResult
     */
    private $customerOrders;

    /**
     * @var CustomerAddressEntity
     */
    private $customerBillingEntity;

    /**
     * @var CustomerAddressEntity
     */
    private $customerShippingEntity;

    /**
     * @var ThreedsIndicatorService
     */
    private $threedsIndicatorService;

    /**
     * @param EntityRepository $orderRepository
     * @param EntityRepository $customerAddressRepository
     */
    public function __construct(
        EntityRepository $orderRepository,
        EntityRepository $customerAddressRepository,
        ThreedsIndicatorService $threedsIndicatorService
    ) {
        $this->orderRepository           = $orderRepository;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->threedsIndicatorService   = $threedsIndicatorService;
    }

    /**
     * Load Mapped Payment Object Data
     *
     * @return void
     * @throws \Exception
     */
    public function initializePayment(PaymentData $paymentData)
    {
        $this->paymentData             = $paymentData;
        $this->customerOrders          = $this->getCustomerOrders();
        $this->customerBillingEntity   = $this->getCustomerAddressEntity(
            $this->getCustomer()->getDefaultBillingAddressId()
        );
        $this->customerShippingEntity  = $this->getCustomerAddressEntity(
            $this->getCustomer()->getDefaultShippingAddressId()
        );
        $this->threedsIndicatorService->initializePaymentData($paymentData, $this->isGuestCheckout());
    }

    /**
     * Fetch the Shipping Indicator from the Order Data
     *
     * @return string
     * @throws \Exception
     */
    public function fetchShippingIndicator()
    {
        return $this->threedsIndicatorService->fetchShippingIndicator();
    }

    /**
     * Fetch whether product/s have been previously re-ordered
     *
     * @return string
     * @throws \Exception
     */
    public function fetchReorderItemsIndicator()
    {
        return $this->threedsIndicatorService->fetchReorderItemsIndicator($this->customerOrders);
    }

    /**
     * Fetch 3DSv2 Account Holder Update Indicator
     *
     * @param \DateTime $modifiedDate
     * @return string
     * @throws \Exception
     */
    public function fetchUpdateIndicator()
    {
        return $this->threedsIndicatorService->fetchUpdateIndicator($this->getCustomerModificationDate());
    }

    /**
     * Fetch 3DSv2 Account Holder Password Change Indicator
     *
     * @return string
     * @throws \Exception
     */
    public function fetchPasswordChangeIndicator()
    {
        return $this->threedsIndicatorService->fetchPasswordChangeIndicator($this->getCustomerDateUpdatedAt());
    }

    /**
     * Fetch 3DSv2 Shipping Usage Indicator
     *
     * @return string
     * @throws \Exception
     */
    public function fetchShippingUsageIndicator()
    {
        $firstUsedDate = $this->getShippingAddressCreationDate();

        if ($firstUsedDate === null) {
            return null;
        }

        return $this->threedsIndicatorService->fetchShippingUsageIndicator($this->getShippingAddressCreationDate());
    }

    /**
     * Fetch 3DSv2 Registration Indicator
     *
     * @return string
     * @throws \Exception
     */
    public function fetchRegistrationIndicator()
    {
        return $this->threedsIndicatorService->fetchRegistrationIndicator($this->getFirstOrderDate());
    }

    /**
     * Get the Customer Lastest Modification date of the used Shipping, Billing or Customer updated At
     *
     * @return \DateTime
     * @throws \Exception
     */
    public function getCustomerModificationDate()
    {
        if ($this->isGuestCheckout() || $this->getCustomer() === null) {
            return new \DateTime();
        }

        $customerModified = $this->getCustomer()->getUpdatedAt() === null ?
            $this->getCustomer()->getCreatedAt() : $this->getCustomer()->getUpdatedAt();

        $unsortedTimestamps = [
            $this->customerBillingEntity->getUpdatedAt() === null ?
                $this->customerBillingEntity->getCreatedAt()->getTimestamp() :
                $this->customerBillingEntity->getUpdatedAt()->getTimestamp(),
            $this->customerShippingEntity->getUpdatedAt() === null ?
                $this->customerShippingEntity->getCreatedAt()->getTimestamp() :
                $this->customerShippingEntity->getUpdatedAt()->getTimestamp(),
            $customerModified->getTimestamp()
        ];

        rsort(
            $unsortedTimestamps,
            SORT_NUMERIC
        );

        if ($unsortedTimestamps[0] === null) {
            $unsortedTimestamps[0] = (new \DateTime())->getTimestamp();
        }

        return \DateTime::createFromFormat('U', $unsortedTimestamps[0]);
    }

    /**
     * Customer Creation Date
     *
     * @return null|string
     * @throws \Exception
     */
    public function getCustomerDateCreatedAt()
    {
        return $this->getCustomer() !== null && $this->getCustomer()->getCreatedAt() !== null ?
            $this->getCustomer()->getCreatedAt()->format(static::DATE_TIME) : null;
    }

    /**
     * Get the Customer Updated At Date
     *
     * @return null|string
     * @throws \Exception
     */
    public function getCustomerDateUpdatedAt()
    {
        return $this->getCustomer() !== null && $this->getCustomer()->getUpdatedAt() !== null ?
            $this->getCustomer()->getUpdatedAt()->format(static::DATE_TIME) : null;
    }

    /**
     * Get Shipping Address First Used - the date of its creation
     *
     * @return null|\DateTimeInterface
     * @throws \Exception
     */
    public function getShippingAddressCreationDate()
    {
        if ($this->getCustomer() === null) {
            return new \DateTime();
        }

        return $this->customerShippingEntity ? $this->customerShippingEntity->getCreatedAt() : null;
    }

    /**
     * Retrieve the count of the orders for the last 24 hours
     *
     * @return int
     */
    public function getTransactionActivityLast24Hours()
    {
        $dateFrom = (new \DateTime())->sub(new \DateInterval('PT24H'));

        $orders = array_filter(
            $this->customerOrders->getElements(),
            function ($order) use ($dateFrom) {
                /** @var OrderEntity $order */
                return $order->getCreatedAt() >= $dateFrom;
            }
        );

        return count($orders);
    }

    /**
     * Retrieve the count of the orders for the previous year
     *
     * @return int
     */
    public function getTransctionActivityPreviousYear()
    {
        $previousYear = (new \DateTime())->sub(new \DateInterval('P1Y'))->format('Y');
        $dateFrom     = (new \DateTime())
            ->setDate($previousYear, 1, 1)
            ->setTime(0, 0);

        $dateTo       = (new \DateTime())
            ->setDate($previousYear, 12, 31)
            ->setTime(23, 59);

        $orders = array_filter(
            $this->customerOrders->getElements(),
            function ($order) use ($dateFrom, $dateTo) {
                return $dateFrom <= $order->getCreatedAt() && $order->getCreatedAt() <= $dateTo;
            }
        );

        return count($orders);
    }

    /**
     * Retrieve all paid Orders for the last 6 months
     *
     * @return int
     */
    public function getPaidOrdersLast6Months()
    {
        $dateFrom = (new \DateTime())->sub(new \DateInterval('P6M'));

        $orders = array_filter(
            $this->customerOrders->getElements(),
            function ($order) use ($dateFrom) {
                return $order->getCreatedAt() >= $dateFrom &&
                    count($order->getTransactions()->filterByState(OrderTransactionStates::STATE_PAID));
            }
        );

        return count($orders);
    }

    /**
     * Get the First Order Date Created
     *
     * @return \DateTimeInterface
     */
    public function getFirstOrderDate()
    {
        return $this->customerOrders->first()->getCreatedAt();
    }

    /**
     * Is Guest Checkout
     *
     * @return bool
     * @throws \Exception
     */
    public function isGuestCheckout()
    {
        return !$this->getCustomer() ||
            $this->paymentData->getOrder()->getOrderCustomer()->getCustomer()->getGuest();
    }

    /**
     * Get the customer from the current Order
     *
     * @return null|CustomerEntity
     * @throws \Exception
     */
    protected function getCustomer()
    {
        return $this->paymentData->getCustomer();
    }

    /**
     * Retrieve the used Customer Address in the payment from the Customer Address Repository
     *
     * @param $identifier
     * @return CustomerAddressEntity
     */
    protected function getCustomerAddressEntity($identifier)
    {
        return $this->customerAddressRepository
            ->search(new Criteria([$identifier]), $this->paymentData->getShopwareContext())
            ->first();
    }

    /**
     * Get All Customer Orders for the current Sales Channel
     *
     * @return EntitySearchResult
     */
    protected function getCustomerOrders()
    {
        $criteria = new Criteria();
        $criteria
            ->addFilter(new EqualsFilter('orderCustomer.customerId', $this->paymentData->getShopwareUserId()))
            ->addFilter(
                new EqualsFilter(
                    'salesChannelId',
                    $this->paymentData->getShopwareContext()->getSource()->getSalesChannelId()
                )
            )
            ->addFilter(
                new ContainsFilter(
                    'salesChannel.paymentMethodIds',
                    $this->paymentData->getPaymentMethodId()
                )
            )
            ->addAssociations(['lineItems', 'transactions', 'transactions.stateMachineState'])
            ->addSorting(new FieldSorting('createdAt'));

        return $this->orderRepository->search($criteria, $this->paymentData->getShopwareContext());
    }
}
