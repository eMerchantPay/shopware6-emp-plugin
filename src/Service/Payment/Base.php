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

namespace Emerchantpay\Genesis\Service\Payment;

use Emerchantpay\Genesis\Constants\Config as ConfigKey;
use Emerchantpay\Genesis\Service\Payment\Exceptions\InvalidRequestAttributes;
use Emerchantpay\Genesis\Service\Payment\Transaction as GenesisTransaction;
use Emerchantpay\Genesis\Utils\Config;
use Emerchantpay\Genesis\Utils\Data\PaymentData;
use Emerchantpay\Genesis\Utils\Data\ReferenceData;
use Emerchantpay\Genesis\Utils\Mappers\PaymentData as PaymentDataMapper;
use Genesis\Api\Constants\Transaction\Names as GenesisNames;
use Genesis\Api\Constants\Transaction\States as GenesisStates;
use Emerchantpay\Genesis\Utils\Mappers\ReferenceData as ReferenceDataMapper;
use Emerchantpay\Genesis\Utils\ReferenceTransactions;
use Genesis\Api\Constants\Transaction\Types as GenesisTypes;
use Genesis\Api\Notification;
use Genesis\Api\Request\Financial\Cancel;
use Genesis\Config as GenesisConfig;
use Genesis\Genesis;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

if (file_exists(dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php')) {
    $loader = require_once dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';
    if ($loader !== true) {
        spl_autoload_unregister([$loader, 'loadClass']);
        $loader->register(false);
    }
}

abstract class Base
{
    public const METHOD_CHECKOUT = 'checkout';
    public const METHOD_DIRECT = 'direct';

    public const PLATFORM_TRANSACTION_PREFIX = 'sw6-';

    /**
     * @var PaymentData
     */
    protected $paymentData;

    /**
     * @var ReferenceData
     */
    protected $referenceData;

    /**
     * Emerchantpay Config Service
     *
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Genesis
     */
    private $genesis;

    /**
     * @var PaymentDataMapper
     */
    private $paymentDataMapper;

    /**
     * @var GenesisTransaction
     */
    private $transactionService;

    /**
     * @var Notification
     */
    private $notification;

    /**
     * @var OrderTransactionStateHandler
     */
    private $orderPaymentStatusHandler;

    /**
     * @var ReferenceDataMapper
     */
    private $referenceDataMapper;

    /**
     * Base constructor.
     */
    public function __construct(
        Config $config,
        LoggerInterface $logger,
        PaymentDataMapper $paymentDataMapper,
        GenesisTransaction $transactionService,
        OrderTransactionStateHandler $orderPaymentStatusHandler,
        ReferenceDataMapper $referenceDataMapper
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->paymentDataMapper = $paymentDataMapper;
        $this->transactionService = $transactionService;
        $this->orderPaymentStatusHandler = $orderPaymentStatusHandler;
        $this->referenceDataMapper = $referenceDataMapper;
    }

    /**
     * Set the Transaction Type Request parameters as usage, transaction types, customer email/phone, etc
     */
    abstract public function setInitialRequestProperties(AsyncPaymentTransactionStruct $transaction, Context $context);

    /**
     * Execute the Request
     */
    abstract public function executeTransactionRequest(): \stdClass;

    /**
     * Get Config option values for the corresponding method
     */
    abstract public function getMethodConfig(): array;

    /**
     * Prepare the Initial Genesis SDK Object
     *
     * @throws \Genesis\Exceptions\DeprecatedMethod
     * @throws \Genesis\Exceptions\InvalidArgument
     * @throws \Genesis\Exceptions\InvalidMethod
     *
     * @return $this
     */
    public function prepareSdkInitialTransactionRequest()
    {
        $this->initializeGenesisConfig();
        $this->genesis = $this->initializeInitialTransactionRequest();

        return $this;
    }

    /**
     * Prepare the Reference Genesis SDK Object
     *
     * @param string $action
     * @param string $transaction_type
     * @param string $token
     *
     * @throws \Genesis\Exceptions\DeprecatedMethod
     * @throws \Genesis\Exceptions\InvalidArgument
     * @throws \Genesis\Exceptions\InvalidMethod
     *
     * @return $this
     */
    public function prepareSdkReferenceTransactionRequest($action, $transaction_type, $token)
    {
        $this->initializeReferenceGenesisConfig($token);
        $this->genesis = $this->initializeReferenceTransactionRequest($action, $transaction_type);

        return $this;
    }

    /**
     * Load the received Notification Data into the SDK Notification Object
     *
     * @throws \Genesis\Exceptions\InvalidArgument
     */
    public function prepareSdkNotificationObject(array $data): void
    {
        $this->initializeGenesisConfig();
        $this->notification = new Notification($data);
    }

    /**
     * Execute Reconcile from the received Genesis Notification
     *
     * @throws InvalidRequestAttributes
     * @throws \Genesis\Exceptions\InvalidResponse
     */
    public function executeReconciliation(): void
    {
        if (!($this->notification instanceof Notification)) {
            throw new InvalidRequestAttributes('Invalid data given for Reconciliation.');
        }

        $this->notification->initReconciliation();
    }

    /**
     * Returns the Genesis SDK object
     */
    public function getGenesis(): Genesis
    {
        return $this->genesis;
    }

    /**
     * Get the Payment Data Mapper
     */
    public function getPaymentDataService(): PaymentDataMapper
    {
        return $this->paymentDataMapper;
    }

    /**
     * Get the Notification Object
     */
    public function getNotification(): Notification
    {
        return $this->notification;
    }

    /**
     * Get the Shopware Status Payment Handler
     */
    protected function getShopwareStatusPaymentHandler(): OrderTransactionStateHandler
    {
        return $this->orderPaymentStatusHandler;
    }

    /**
     * Get the Reference Data Mapper
     */
    public function getReferenceDataService(): ReferenceDataMapper
    {
        return $this->referenceDataMapper;
    }

    /**
     * Load Genesis with the Desired Transaction Request
     */
    abstract protected function initializeInitialTransactionRequest(): Genesis;

    /**
     * @throws \Genesis\Exceptions\InvalidArgument
     */
    abstract protected function initializeGenesisConfig(): void;

    /**
     * Retrieve the ShopName
     */
    protected function getShopName(): string
    {
        return $this->config->getShopName();
    }

    protected function getStringPaymentMode(): string
    {
        $mode = null;

        if (array_key_exists(ConfigKey::CHECKOUT_LIVE_MODE, $this->getMethodConfig())) {
            $mode = $this->getMethodConfig()[ConfigKey::CHECKOUT_LIVE_MODE];
        }

        return $mode ? 'live' : 'test';
    }

    /**
     * Get the Logger
     */
    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Get the Transaction
     */
    protected function getTransaction(): GenesisTransaction
    {
        return $this->transactionService;
    }

    protected function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Update Shopware Order Payment State
     */
    protected function updatePaymentState(
        string $transactionId,
        string $paymentStatus,
        Context $context,
        ?string $transactionType = null
    ): void {
        $this->getLogger()->info(
            sprintf(
                '%s transaction (%s) with Id: %s',
                ucfirst($paymentStatus),
                GenesisNames::getName($transactionType),
                $transactionId
            )
        );

        switch ($paymentStatus) {
            case GenesisStates::APPROVED:
                if ($this->isAuthorize($transactionType)) {
                    $this->getShopwareStatusPaymentHandler()->process($transactionId, $context);

                    break;
                }

                $this->getShopwareStatusPaymentHandler()->paid($transactionId, $context);

                break;
            case GenesisStates::PENDING_ASYNC:
            case GenesisStates::PENDING:
                $this->getShopwareStatusPaymentHandler()->process($transactionId, $context);

                break;
            case GenesisStates::ERROR:
                $this->getShopwareStatusPaymentHandler()->fail($transactionId, $context);

                break;
            case GenesisStates::TIMEOUT:
            case GenesisStates::VOIDED:
            case GenesisStates::DECLINED:
                $this->getShopwareStatusPaymentHandler()->cancel($transactionId, $context);

                break;
            case GenesisStates::REFUNDED:
                $this->getShopwareStatusPaymentHandler()->refund($transactionId, $context);

                break;
        }
    }

    /**
     * Checks if the Given Transaction Type is Authorization
     */
    protected function isAuthorize($transactionType): bool
    {
        return GenesisTypes::isAuthorize($transactionType);
    }

    /**
     * Load the Reference Attributes into the Reference Genesis Request
     */
    protected function loadReferenceAttributes(): void
    {
        $this->getGenesis()->request()
            ->setTransactionId($this->referenceData->getTransactionId())
            ->setUsage($this->referenceData->getUsage())
            ->setRemoteIp($this->referenceData->getRemoteIp())
            ->setReferenceId($this->referenceData->getReferenceId());

        if (($this->getGenesis()->request() instanceof Cancel)) {
            return;
        }

        $this->getGenesis()->request()
            ->setAmount($this->referenceData->getAmount())
            ->setCurrency($this->referenceData->getCurrency());
    }

    /**
     * Generate plugin Transaction Id
     */
    protected function generateTransactionId(string $prefix = self::PLATFORM_TRANSACTION_PREFIX, int $maxLength = 32)
    {
        $prefixLen = strlen($prefix);

        return $prefix . substr(Uuid::randomHex(), $prefixLen);
    }

    /**
     * @param $action
     * @param $transaction_type
     *
     * @throws \Genesis\Exceptions\DeprecatedMethod
     * @throws \Genesis\Exceptions\InvalidArgument
     * @throws \Genesis\Exceptions\InvalidMethod
     */
    private function initializeReferenceTransactionRequest($action, $transaction_type): Genesis
    {
        switch ($action) {
            case ReferenceTransactions::ACTION_CAPTURE:
                return new Genesis(GenesisTypes::getCaptureTransactionClass($transaction_type));

                break;
            case ReferenceTransactions::ACTION_VOID:
                return new Genesis(
                    GenesisTypes::getFinancialRequestClassForTrxType(GenesisTypes::VOID)
                );

                break;
            case ReferenceTransactions::ACTION_REFUND:
                return new Genesis(GenesisTypes::getRefundTransactionClass($transaction_type));

                break;
            default:
                throw new \Exception('Invalid Reference action given.');
        }
    }

    /**
     * Initialize the Reference Transaction Request Config
     *
     * @param string $token
     *
     * @throws \Genesis\Exceptions\InvalidArgument
     */
    private function initializeReferenceGenesisConfig($token): void
    {
        $this->initializeGenesisConfig();

        GenesisConfig::setToken($token);
    }
}
