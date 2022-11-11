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

use Emerchantpay\Genesis\Constants\ReturnUrl as ReturnUrlStatus;
use Emerchantpay\Genesis\Service\Flow\ReturnUrl;
use Emerchantpay\Genesis\Utils\Config;
use EMerchantPay\Genesis\Utils\Data\PaymentData as PaymentDataModel;
use Emerchantpay\Genesis\Utils\Mappers\Exceptions\InvalidPaymentData;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class PaymentData
 */
class PaymentData
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ReturnUrl
     */
    private $returnUrl;

    /**
     * @var PaymentDataModel
     */
    private $paymentDataModel;

    /**
     * @var Context
     */
    private $salesContext;

    /**
     * @var EntityRepositoryInterface
     */
    private $currencyRepository;

    /**
     * @var CurrencyEntity
     */
    private $currencyEntity;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderAddressRepository;

    /**
     * @var OrderAddressEntity
     */
    private $billingAddressEntity = null;

    /**
     * @var EntityRepositoryInterface
     */
    private $stateRepository;

    /**
     * @var CountryStateEntity
     */
    private $countryStateEntity = null;

    /**
     * @var EntityRepositoryInterface
     */
    private $countryRepository;

    /**
     * @var CountryEntity
     */
    private $countryEntity = null;

    /**
     * @var EntityRepositoryInterface
     */
    private $customerAddressRepository;

    /**
     * @var CustomerAddressEntity
     */
    private $shippingAddressEntity = null;

    /**
     * @var CountryStateEntity
     */
    private $shippingStateEntity = null;

    /**
     * @var CountryEntity
     */
    private $shippingCountryEntity = null;

    /**
     * @var CustomerEntity
     */
    private $shopwareCustomerEntity = null;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        Config $config,
        ReturnUrl $returnUrl,
        PaymentDataModel $paymentDataModel,
        EntityRepositoryInterface $currencyRepository,
        EntityRepositoryInterface $orderAddressRepository,
        EntityRepositoryInterface $stateRepository,
        EntityRepositoryInterface $countryRepository,
        EntityRepositoryInterface $customerAddressRepository,
        RouterInterface $router
    ) {
        $this->config = $config;
        $this->returnUrl = $returnUrl;
        $this->paymentDataModel = $paymentDataModel;
        $this->currencyRepository = $currencyRepository;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->stateRepository = $stateRepository;
        $this->countryRepository = $countryRepository;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->router = $router;
    }

    /**
     * Map the Payment data from the Shopware Order Transaction
     *
     * @throws InvalidPaymentData
     */
    public function loadFromShopwareTransaction(
        string $pluginTransactionId,
        AsyncPaymentTransactionStruct $transaction,
        Context $context
    ): void {
        try {
            $this->invoke($transaction, $context);

            // Assign the current Shop Context
            $this->paymentDataModel->setShopwareContext($context);
            $this->paymentDataModel->setOrder($transaction->getOrder());
            $this->paymentDataModel->setPaymentMethodId($transaction->getOrderTransaction()->getPaymentMethodId());

            // Transaction Attributes
            $this->paymentDataModel->setTransactionId($pluginTransactionId);
            $this->paymentDataModel->setShopwarePaymentId($transaction->getOrderTransaction()->getId());
            $this->paymentDataModel->setAmount($transaction->getOrderTransaction()->getAmount()->getTotalPrice());
            $this->paymentDataModel->setCurrency($this->currencyEntity->getIsoCode());

            // Customer Attributes
            $this->paymentDataModel->setEmail($transaction->getOrder()->getOrderCustomer()->getEmail());
            $this->paymentDataModel->setPhone($this->billingAddressEntity->getPhoneNumber());

            // Billing Attributes
            $this->paymentDataModel->setBillingFirstName($this->billingAddressEntity->getFirstName());
            $this->paymentDataModel->setBillingLastName($this->billingAddressEntity->getLastName());
            $this->paymentDataModel->setBillingAddress($this->billingAddressEntity->getStreet());
            $this->paymentDataModel->setBillingZipcode($this->billingAddressEntity->getZipcode());
            $this->paymentDataModel->setBillingCity($this->billingAddressEntity->getCity());
            $this->paymentDataModel->setBillingState(
                $this->countryStateEntity ? $this->countryStateEntity->getShortCode() : null
            );
            $this->paymentDataModel->setBillingCountry($this->countryEntity->getIso());

            // Shipping Attributes
            $this->paymentDataModel->setShippingFirstName($this->shippingAddressEntity->getFirstName());
            $this->paymentDataModel->setShippingLastName($this->shippingAddressEntity->getLastName());
            $this->paymentDataModel->setShippingAddress($this->shippingAddressEntity->getStreet());
            $this->paymentDataModel->setShippingZipcode($this->shippingAddressEntity->getZipcode());
            $this->paymentDataModel->setShippingCity($this->shippingAddressEntity->getCity());
            $this->paymentDataModel->setShippingState(
                $this->shippingStateEntity ? $this->shippingStateEntity->getShortCode() : null
            );
            $this->paymentDataModel->setShippingCountry($this->shippingCountryEntity->getIso());

            $this->paymentDataModel->setOrderItems($this->getLineItems($transaction->getOrder()->getLineItems()));

            // Notification endpoint
            $this->paymentDataModel->setNotificationUrl(
                $this->router->generate(
                    'frontend.emerchantpay.ipn.checkout',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            );

            // Return URLs
            $this->paymentDataModel->setSuccessUrl(
                $this->returnUrl->generateReturnUrl(
                    $this->buildReturnUrl($transaction->getReturnUrl(), ReturnUrlStatus::SUCCESS)
                )
            );
            $this->paymentDataModel->setCancelUrl(
                $this->returnUrl->generateReturnUrl(
                    $this->buildReturnUrl($transaction->getReturnUrl(), ReturnUrlStatus::CANCEL)
                )
            );
            $this->paymentDataModel->setFailureUrl(
                $this->returnUrl->generateReturnUrl(
                    $this->buildReturnUrl($transaction->getReturnUrl(), ReturnUrlStatus::FAILURE)
                )
            );

            $this->paymentDataModel->setOrderId($transaction->getOrderTransaction()->getOrderId());
            $this->paymentDataModel->setShopwareUserId(
                $this->shopwareCustomerEntity !== null ? $this->shopwareCustomerEntity->getId() : null
            );
        } catch (InvalidPaymentData $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new InvalidPaymentData($e->getMessage());
        }
    }

    public function getPaymentData(): PaymentDataModel
    {
        return $this->paymentDataModel;
    }

    /**
     * Build Success, Cancel and Failure URL used for the Payment and processed in the Finalize method
     *
     * @param string $return_url
     * @param string $status
     *
     * @return string
     */
    protected function buildReturnUrl($return_url, $status)
    {
        return $return_url . '&' . http_build_query(['status' => $status]);
    }

    /**
     * Load the Shopware 6 data required for the Genesis Transaction Request
     *
     * @throws InvalidPaymentData
     */
    private function invoke(AsyncPaymentTransactionStruct $transaction, Context $context): void
    {
        $this->salesContext = $context;
        $this->loadCurrency($transaction->getOrder()->getCurrencyId());
        $this->loadBillingAddress($transaction->getOrder()->getBillingAddressId());

        if ($transaction->getOrder()->getOrderCustomer() !== null) {
            $this->loadShippingAddress(
                $transaction->getOrder()->getOrderCustomer()->getCustomer()->getDefaultShippingAddressId()
            );

            $this->loadShopwareOrderCustomer($transaction->getOrder()->getOrderCustomer());
        }
    }

    /**
     * Load the Currency Entity
     *
     * @throws \Exception
     */
    private function loadCurrency(string $currency_id): void
    {
        $this->currencyEntity = $this->loadEntity($this->currencyRepository, $currency_id, 'currency');
    }

    /**
     * Load the BillingAddress entity
     *
     * @throws InvalidPaymentData
     */
    private function loadBillingAddress(string $order_address_id): void
    {
        $this->billingAddressEntity = $this->loadEntity(
            $this->orderAddressRepository,
            $order_address_id,
            'billing address',
            false
        );

        if ($this->billingAddressEntity === null) {
            return;
        }

        if ($this->billingAddressEntity->getCountryId() !== null) {
            $this->countryEntity = $this->loadEntity(
                $this->countryRepository,
                $this->billingAddressEntity->getCountryId(),
                'billing country',
                false
            );
        }

        if ($this->billingAddressEntity->getCountryStateId() !== null) {
            $this->countryStateEntity = $this->loadEntity(
                $this->stateRepository,
                $this->billingAddressEntity->getCountryStateId(),
                'billing state',
                false
            );
        }
    }

    /**
     * Load the Shipping Address
     *
     * @throws InvalidPaymentData
     */
    private function loadShippingAddress(string $default_shipping_address_id): void
    {
        $this->shippingAddressEntity = $this->loadEntity(
            $this->customerAddressRepository,
            $default_shipping_address_id,
            'shipping address',
            false
        );

        if ($this->shippingAddressEntity === null) {
            return;
        }

        if ($this->shippingAddressEntity->getCountryId() !== null) {
            $this->shippingCountryEntity = $this->loadEntity(
                $this->countryRepository,
                $this->shippingAddressEntity->getCountryId(),
                'shipping country',
                false
            );
        }

        if ($this->shippingAddressEntity->getCountryId() !== null) {
            $this->shippingStateEntity = $this->loadEntity(
                $this->stateRepository,
                $this->shippingAddressEntity->getCountryId(),
                'shipping state',
                false
            );
        }
    }

    /**
     * Search into Database and load the Entity
     *
     * @throws InvalidPaymentData
     *
     * @return mixed|null
     */
    private function loadEntity(
        EntityRepositoryInterface $repository,
        string $identifier,
        string $entity_name = '',
        bool $data_required = true
    ) {
        try {
            /** @var EntitySearchResult $result */
            $result = $repository->search(
                new Criteria([$identifier]),
                $this->salesContext
            );

            if ($data_required && $result->getTotal() === 0) {
                throw new InvalidPaymentData("Invalid $entity_name identifier");
            }

            if ($result->getTotal() === 1) {
                return current($result->getElements());
            }
        } catch (\Exception $e) {
            if ($data_required) {
                throw new InvalidPaymentData($e->getMessage());
            }
        }

        return null;
    }

    /**
     * Load the Shopware Order Customer
     */
    private function loadShopwareOrderCustomer(OrderCustomerEntity $orderCustomerEntity): void
    {
        $this->shopwareCustomerEntity = $orderCustomerEntity->getCustomer();
    }

    /**
     * Build array with the Order Items
     *
     * @throws \Exception
     */
    private function getLineItems(OrderLineItemCollection $items): array
    {
        $orderedItems = [];

        if ($items === null || $items->count() === 0) {
            return $orderedItems;
        }

        /** @var OrderLineItemEntity $item */
        foreach ($items->getIterator() as $item) {
            $orderedItems[] = [
                'quantity'     => $item->getQuantity(),
                'article_name' => $item->getLabel(),
                'is_good'      => $item->getGood(),
                'product_id'   => $item->getProductId()
            ];
        }

        return $orderedItems;
    }
}
