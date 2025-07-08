<?php

declare(strict_types=1);

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

namespace Emerchantpay\Genesis\Service\Payment;

use Emerchantpay\Genesis\Constants\Config as ConfigKey;
use Emerchantpay\Genesis\Core\Payment\Transaction\TransactionEntity;
use Emerchantpay\Genesis\Service\Payment\Exceptions\InvalidGenesisRequest;
use Emerchantpay\Genesis\Service\Payment\Exceptions\InvalidRequestAttributes;
use Emerchantpay\Genesis\Service\Payment\Helpers\ThreedsService;
use Emerchantpay\Genesis\Service\Payment\Transaction as GenesisTransaction;
use Emerchantpay\Genesis\Service\TokenizationService;
use Emerchantpay\Genesis\Utils\Config;
use Emerchantpay\Genesis\Utils\Mappers\PaymentData as PaymentDataMapper;
use Emerchantpay\Genesis\Utils\Mappers\ReferenceData as ReferenceDataMapper;
use Emerchantpay\Genesis\Utils\ReferenceTransactions;
use Genesis\Api\Constants\Endpoints;
use Genesis\Api\Constants\Environments;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\MerchantRisk\DeliveryTimeframes;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\Purchase\Categories;
use Genesis\Api\Constants\Transaction\States as GenesisStates;
use Genesis\Api\Constants\Transaction\Types as GenesisTypes;
use Genesis\Api\Request\Wpf\Create as GenesisWpfCreate;
use Genesis\Api\Response as GenesisSdkResponse;
use Genesis\Config as GenesisConfig;
use Genesis\Exceptions\ErrorParameter as GenesisErrorParameter;
use Genesis\Exceptions\Exception as GenesisException;
use Genesis\Genesis;
use Genesis\Utils\Common as CommonUtils;
use Genesis\Utils\Currency;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;

class Checkout extends Base
{
    public const CHECKOUT_TRANSACTION_TYPE = 'web_payment';

    /**
     * @var TokenizationService
     */
    private $tokenizationService;

    /**
     * @var ThreedsService $threedsService
     */
    private $threedsService;

    public function __construct(
        Config $config,
        LoggerInterface $logger,
        PaymentDataMapper $paymentDataMapper,
        GenesisTransaction $transactionService,
        OrderTransactionStateHandler $orderPaymentStatusHandler,
        ReferenceDataMapper $referenceDataMapper,
        TokenizationService $tokenizationService,
        ThreedsService $threedsService
    ) {
        parent::__construct(
            $config,
            $logger,
            $paymentDataMapper,
            $transactionService,
            $orderPaymentStatusHandler,
            $referenceDataMapper
        );
        $this->tokenizationService = $tokenizationService;
        $this->threedsService      = $threedsService;
    }

    public function getMethod()
    {
        return self::METHOD_CHECKOUT;
    }

    /**
     * Define the Method Config array values
     */
    public function getMethodConfig(): array
    {
        return $this->getConfig()->getCheckout();
    }

    /**
     * @throws InvalidRequestAttributes
     * @throws \Emerchantpay\Genesis\Utils\Mappers\Exceptions\InvalidPaymentData
     */
    public function setInitialRequestProperties(AsyncPaymentTransactionStruct $transaction, Context $context): void
    {
        $this->getPaymentDataService()->loadFromShopwareTransaction(
            $this->generateTransactionId(),
            $transaction,
            $context
        );
        $this->paymentData = $this->getPaymentDataService()->getPaymentData();

        $this->loadRequestAttributes();
    }

    public function setReferenceRequestProperties(TransactionEntity $transaction, $clientIpAddress, $action): void
    {
        $this->getReferenceDataService()->loadFromArray([
            'transaction_id'      => $this->generateTransactionId(),
            'reference_id'        => $transaction->getUniqueId(),
            'amount'              => Currency::exponentToAmount($transaction->getAmount(), $transaction->getCurrency()),
            'currency'            => $transaction->getCurrency(),
            'order_id'            => $transaction->getOrderId(),
            'terminal_token'      => $transaction->getTerminalToken(),
            'remote_ip'           => $clientIpAddress,
            'usage'               => ucfirst($action) . " via " . $this->getShopName(),
            'shopware_payment_id' => $transaction->getShopwarePaymentId(),
        ]);
        $this->referenceData = $this->getReferenceDataService()->getReferenceData();

        $this->loadReferenceAttributes();
    }

    /**
     * @throws \Genesis\Exceptions\ErrorParameter
     * @throws \Genesis\Exceptions\InvalidArgument
     * @throws \Genesis\Exceptions\InvalidClassMethod
     * @throws \Genesis\Exceptions\InvalidResponse
     * @throws \Exception
     */
    public function executeTransactionRequest(): \stdClass
    {
        $tokenizationService = $this->tokenizationService;

        try {
            $genesis = $this->getGenesis();
            $genesis->execute();

            $responseObject = $genesis->response()->getResponseObject();

            $this->getTransaction()->addTransactionFromRequest(
                $this->paymentData,
                $genesis,
                $this->getStringPaymentMode(),
                $this->getMethod()
            );

            if (!$genesis->response()->isNew()) {
                $message = isset($responseObject->message) ? $responseObject->message : 'Unknown decline reason';
                $this->getLogger()->info(
                    $message,
                    (array) $responseObject
                );

                throw new InvalidGenesisRequest($message);
            }

            if (isset($responseObject->consumer_id) && !empty($responseObject->consumer_id)) {
                $consumerId = $responseObject->consumer_id;
                $tokenizationService->saveConsumerId($this->paymentData->getEmail(), $consumerId);
            }

            return $genesis->response()->getResponseObject();
        } catch (GenesisErrorParameter $e) {
            $this->getLogger()->debug(
                'Genesis SDK Transaction Request Error Parameter - ' . $e->getMessage(),
                [0 => $e->getTraceAsString()]
            );

            throw new InvalidRequestAttributes($e->getMessage());
        } catch (GenesisException $e) {
            $this->getLogger()->error(
                'Genesis SDK Transaction Request General Error - ' . $e->getMessage(),
                [0 => $e->getTraceAsString()]
            );

            throw new InvalidGenesisRequest($e->getMessage());
        }
    }

    /**
     * Execute Reference Transaction Request
     *
     * @return GenesisSdkResponse
     * @throws InvalidGenesisRequest
     */
    public function executeReferenceRequest(): GenesisSdkResponse
    {
        try {
            $genesis = $this->getGenesis();
            $genesis->execute();

            $responseObject = $genesis->response()->getResponseObject();

            $this->getTransaction()->addReferenceTransactionFromRequest(
                $this->referenceData,
                $genesis,
                $this->getStringPaymentMode(),
                $this->getMethod()
            );

            if (!($genesis->response()->isApproved() || $genesis->response()->isPendingAsync())) {
                $message = $responseObject->message ?? 'Unknown decline reason';

                $this->getLogger()->info($message, (array) $responseObject);

                throw new InvalidGenesisRequest($message);
            }

            return $genesis->response();
        } catch (\Exception $e) {
            $this->getLogger()->error(
                'Genesis SDK Reference Transaction Request General Error - ' . $e->getMessage(),
                [0 => $e->getTraceAsString()]
            );

            throw new InvalidGenesisRequest($e->getMessage());
        }
    }

    /**
     * Used for the specifics of the Genesis Authorize flow
     */
    public function updateCapturePaymentState(
        TransactionEntity $transaction,
        string $action,
        GenesisSdkResponse $responseObject,
        Context $context
    ): void {
        if (
        !(
            GenesisTypes::isCapture($transaction->getTransactionType()) ||
            $this->isAuthorize($transaction->getTransactionType())
        )
        ) {
            return;
        }

        if (
            $action === ReferenceTransactions::ACTION_VOID &&
            $this->isAuthorize($transaction->getTransactionType())
        ) {
            return;
        }

        if ($responseObject->isApproved() || $responseObject->isPendingAsync()) {
            $this->updatePaymentState(
                $transaction->getShopwarePaymentId(),
                $this->getPaymentStatusByAction($action),
                $context,
                $responseObject->getResponseObject()?->transaction_type
            );
        }
    }

    /**
     * Translate the Shopware Backend Administration Controller Action
     *
     * @return string
     */
    protected function getPaymentStatusByAction($action): string
    {
        switch ($action) {
            case ReferenceTransactions::ACTION_CAPTURE:
                return GenesisStates::APPROVED;

                break;
            case ReferenceTransactions::ACTION_REFUND:
                return GenesisStates::REFUNDED;

                break;
            case ReferenceTransactions::ACTION_VOID:
                return GenesisStates::VOIDED;

                break;
        }
    }

    /**
     * @param $transactionType
     * @return bool
     */
    protected function isAuthorize($transactionType): bool
    {
        switch ($transactionType) {
            case GenesisTypes::GOOGLE_PAY:
                return in_array(
                    ConfigKey::GOOGLE_PAY_TRANSACTION_PREFIX . ConfigKey::GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE,
                    $this->getMethodConfig()[ConfigKey::CHECKOUT_TRANSACTION_TYPES]
                );
            case GenesisTypes::PAY_PAL:
                return in_array(
                    ConfigKey::PAYPAL_TRANSACTION_PREFIX . ConfigKey::PAYPAL_PAYMENT_TYPE_AUTHORIZE,
                    $this->getMethodConfig()[ConfigKey::CHECKOUT_TRANSACTION_TYPES]
                );
            case GenesisTypes::APPLE_PAY:
                return in_array(
                    ConfigKey::APPLE_PAY_TRANSACTION_PREFIX . ConfigKey::APPLE_PAY_PAYMENT_TYPE_AUTHORIZE,
                    $this->getMethodConfig()[ConfigKey::CHECKOUT_TRANSACTION_TYPES]
                );
        }

        return parent::isAuthorize($transactionType);
    }

    /**
     * @throws InvalidRequestAttributes
     */
    protected function loadRequestAttributes(): void
    {
        try {
            /** @var GenesisWpfCreate $request */
            $request = $this->getGenesis()->request();
            $request
                ->setTransactionId($this->paymentData->getTransactionId())
                ->setAmount($this->paymentData->getAmount())
                ->setCurrency($this->paymentData->getCurrency())
                ->setUsage("Payment via {$this->getShopName()}")
                ->setDescription($this->paymentData->buildOrderDescriptionText())

                ->setCustomerEmail($this->paymentData->getEmail())
                ->setCustomerPhone($this->paymentData->getPhone())

                ->setReturnSuccessUrl($this->paymentData->getSuccessUrl())
                ->setReturnPendingUrl($this->paymentData->getSuccessUrl())
                ->setReturnCancelUrl($this->paymentData->getCancelUrl())
                ->setReturnFailureUrl($this->paymentData->getFailureUrl())
                ->setNotificationUrl($this->paymentData->getNotificationUrl())

                ->setBillingFirstName($this->paymentData->getBillingFirstName())
                ->setBillingLastName($this->paymentData->getBillingLastName())
                ->setBillingAddress1($this->paymentData->getBillingAddress())
                ->setBillingZipCode($this->paymentData->getBillingZipcode())
                ->setBillingCity($this->paymentData->getBillingCity())
                ->setBillingState($this->paymentData->getBillingState())
                ->setBillingCountry($this->paymentData->getBillingCountry())

                ->setShippingFirstName($this->paymentData->getShippingFirstName())
                ->setShippingLastName($this->paymentData->getShippingLastName())
                ->setShippingAddress1($this->paymentData->getShippingAddress())
                ->setShippingZipCode($this->paymentData->getShippingZipcode())
                ->setShippingCity($this->paymentData->getShippingCity())
                ->setShippingState($this->paymentData->getShippingState())
                ->setShippingCountry($this->paymentData->getShippingCountry())
                ->setLanguage($this->getMethodConfig()[ConfigKey::CHECKOUT_LANGUAGE]);

            if ($this->getMethodConfig()[ConfigKey::CHECKOUT_TOKENIZATION] === true) {
                $request->setRememberCard('true');
                $request->setConsumerId(
                    $this->tokenizationService->getConsumerId($this->paymentData->getEmail())
                );
            }

            if ($this->getMethodConfig()[ConfigKey::CHECKOUT_THREEDS_ALLOWED] === true) {
                $this->populateThreedsParameters();
            }

            if ((float) $this->paymentData->getAmount() <= $this->getScaExemptionAmount()) {
                $request->setScaExemption($this->getMethodConfig()[ConfigKey::CHECKOUT_SCA_EXEMPTION]);
            }

            $this->appendTransactionTypes();
        } catch (GenesisException $e) {
            throw new InvalidRequestAttributes($e->getMessage());
        } catch (\Exception $e) {
            throw new InvalidRequestAttributes($e->getMessage());
        }
    }

    /**
     * Process the Genesis Notification
     *
     * @throws InvalidRequestAttributes
     */
    public function processNotification(Context $context): void
    {
        /** @var \stdClass $reconcileObject */
        $reconcileObject = $this->getNotification()->getReconciliationObject();

        $transactionRepository = $this->getTransaction();

        /** @var TransactionEntity $result */
        $transactionEntity = $transactionRepository->findWebPayment($reconcileObject->unique_id);

        if ($transactionEntity === null) {
            throw new InvalidRequestAttributes('Given unique id is invalid');
        }

        // Load correct payment from the Reconcile Object
        $payment = $reconcileObject;
        if (isset($reconcileObject->payment_transaction)) {
            $payment = $this->populatePaymentTransaction($reconcileObject);
        }

        $this->getLogger()->debug("Checkout Payment Object {$this->getMethod()}", (array) $payment);

        $actionTransaction = $transactionRepository->findPaymentTransaction($payment->unique_id);

        if ($actionTransaction === null && $payment->unique_id !== $transactionEntity->getUniqueId()) {
            $transactionRepository->addTransactionFromNotification(
                $transactionEntity,
                $payment,
                $this->getNotification()
            );
        }

        if ($actionTransaction === null && $payment->unique_id === $transactionEntity->getUniqueId()) {
            $transactionRepository->updateTransactionFromNotification(
                $transactionEntity,
                $payment,
                $this->getNotification()
            );
        }

        if ($actionTransaction instanceof TransactionEntity) {
            $transactionRepository->updateTransactionFromNotification(
                $actionTransaction,
                $payment,
                $this->getNotification()
            );

            $referenceTransaction = $transactionRepository->findPaymentReference(
                $actionTransaction->getUniqueId()
            );

            if ($referenceTransaction !== null) {
                $paymentCopy = clone $payment;
                $paymentCopy->status = $this->transformNotificationState($paymentCopy->status);

                $transactionRepository->updateTransactionFromNotification(
                    $referenceTransaction,
                    $paymentCopy,
                    $this->getNotification()
                );
            }
        }

        $this->updatePaymentState(
            $transactionEntity->getShopwarePaymentId(),
            $payment->status,
            $context,
            isset($payment->transaction_type) ? $payment->transaction_type : null
        );
    }

    /**
     * Initialize Web Payment Form
     *
     * @throws \Genesis\Exceptions\DeprecatedMethod
     * @throws \Genesis\Exceptions\InvalidArgument
     * @throws \Genesis\Exceptions\InvalidMethod
     * @throws \Exception
     */
    protected function initializeInitialTransactionRequest(): Genesis
    {
        try {
            return new Genesis('Wpf\Create');
        } catch (GenesisException $e) {
            $this->getLogger()->error(
                'Genesis SDK initialization - ' . $e->getMessage(),
                [0 => $e->getTraceAsString()]
            );

            throw new InvalidRequestAttributes($e->getMessage());
        }
    }

    /**
     * Append the Genesis WPF Request Transaction Types
     */
    protected function appendTransactionTypes(): void
    {
        $types = $this->getCheckoutTransactionTypes();

        /** @var GenesisWpfCreate $request */
        $request = $this->getGenesis()->request();

        foreach ($types as $transactionType) {
            if (is_array($transactionType)) {
                $request->addTransactionType(
                    $transactionType['name'],
                    $transactionType['parameters']
                );

                continue;
            }

            $parameters = $this->getCustomRequiredAttributes($transactionType);

            $request->addTransactionType(
                $transactionType,
                $parameters
            );

            unset($parameters);
        }
    }

    /**
     * Get the Customer Required Attributes for specific Transaction Types
     */
    protected function getCustomRequiredAttributes(string $transactionType): array
    {
        $parameters = [];

        switch ($transactionType) {
            case GenesisTypes::IDEBIT_PAYIN:
            case GenesisTypes::INSTA_DEBIT_PAYIN:
                $parameters = [
                    'customer_account_id' => $this->paymentData->getShopwareUserId()
                ];
                break;
            case GenesisTypes::TRUSTLY_SALE:
                $parameters = [
                    'user_id' => $this->paymentData->getShopwareUserId()
                ];
                break;
            case GenesisTypes::ONLINE_BANKING_PAYIN:
                $selectedBankCodes = $this->getMethodConfig()[ConfigKey::CHECKOUT_BANK_CODES];
                if (CommonUtils::isValidArray($selectedBankCodes)) {
                    $parameters['bank_codes'] = array_map(
                        function ($value) {
                            return ['bank_code' => $value];
                        },
                        $selectedBankCodes
                    );
                }
                break;
            case GenesisTypes::PAYSAFECARD:
                $parameters = [
                    'customer_id' => $this->paymentData->getShopwareUserId()
                ];
                break;
        }

        return $parameters;
    }

    /**
     * Process the Checkout Config and provides the transaction type names with their params
     */
    protected function getCheckoutTransactionTypes(): array
    {
        $processedList = [];
        $aliasMap = [];

        $selectedTypes = $this->orderCardTransactionTypes(
            $this->getMethodConfig()[ConfigKey::CHECKOUT_TRANSACTION_TYPES]
        );

        $aliasMap = [
            ConfigKey::GOOGLE_PAY_TRANSACTION_PREFIX . ConfigKey::GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE =>
                GenesisTypes::GOOGLE_PAY,
            ConfigKey::GOOGLE_PAY_TRANSACTION_PREFIX . ConfigKey::GOOGLE_PAY_PAYMENT_TYPE_SALE      =>
                GenesisTypes::GOOGLE_PAY,
            ConfigKey::PAYPAL_TRANSACTION_PREFIX . ConfigKey::PAYPAL_PAYMENT_TYPE_AUTHORIZE         =>
                GenesisTypes::PAY_PAL,
            ConfigKey::PAYPAL_TRANSACTION_PREFIX . ConfigKey::PAYPAL_PAYMENT_TYPE_SALE              =>
                GenesisTypes::PAY_PAL,
            ConfigKey::PAYPAL_TRANSACTION_PREFIX . ConfigKey::PAYPAL_PAYMENT_TYPE_EXPRESS           =>
                GenesisTypes::PAY_PAL,
            ConfigKey::APPLE_PAY_TRANSACTION_PREFIX . ConfigKey::APPLE_PAY_PAYMENT_TYPE_AUTHORIZE   =>
                GenesisTypes::APPLE_PAY,
            ConfigKey::APPLE_PAY_TRANSACTION_PREFIX . ConfigKey::APPLE_PAY_PAYMENT_TYPE_SALE        =>
                GenesisTypes::APPLE_PAY,
        ];

        foreach ($selectedTypes as $selectedType) {
            if (!array_key_exists($selectedType, $aliasMap)) {
                $processedList[] = $selectedType;

                continue;
            }

            $transactionType = $aliasMap[$selectedType];

            $processedList[$transactionType]['name'] = $transactionType;

            $key = $this->getCustomParameterKey($transactionType);

            $processedList[$transactionType]['parameters'][] = [
                $key => str_replace(
                    [
                        ConfigKey::GOOGLE_PAY_TRANSACTION_PREFIX,
                        ConfigKey::PAYPAL_TRANSACTION_PREFIX,
                        ConfigKey::APPLE_PAY_TRANSACTION_PREFIX,
                    ],
                    '',
                    $selectedType
                ),
            ];
        }

        return $processedList;
    }

    /**
     * Initialize the SDK Config
     *
     * @throws \Genesis\Exceptions\InvalidArgument
     */
    protected function initializeGenesisConfig(): void
    {
        $config = $this->getMethodConfig();

        GenesisConfig::setEndpoint(Endpoints::EMERCHANTPAY);
        GenesisConfig::setEnvironment(
            $config[ConfigKey::CHECKOUT_LIVE_MODE] ?
                Environments::PRODUCTION : Environments::STAGING
        );
        GenesisConfig::setUsername($config[ConfigKey::CHECKOUT_USERNAME]);
        GenesisConfig::setPassword($config[ConfigKey::CHECKOUT_PASSWORD]);
    }

    /**
     * @param \stdClass $reconcileObject The Genesis Reconcile Object
     *
     * @return \stdClass
     */
    protected function populatePaymentTransaction($reconcileObject)
    {
        if (isset($reconcileObject->payment_transaction->unique_id)) {
            return $reconcileObject->payment_transaction;
        }

        if (count($reconcileObject->payment_transaction) > 1) {
            $paymentTransactions = $reconcileObject->payment_transaction;
            $lastTransaction = $this->getTransaction()->findPaymentTransaction(
                $reconcileObject->unique_id
            );

            if (!isset($lastTransaction)) {
                return $paymentTransactions[0];
            }

            foreach ($paymentTransactions as $paymentTransaction) {
                if ($paymentTransaction->unique_id === $lastTransaction->getReferenceId()) {
                    return $paymentTransaction;
                }
            }

            return $paymentTransactions[0];
        }
    }

    /**
     * Transform state received from the Notification and used for update of the Reference Transaction
     */
    protected function transformNotificationState(string $state)
    {
        switch ($state) {
            case GenesisStates::VOIDED:
            case GenesisStates::APPROVED:
            case GenesisStates::REFUNDED:
                return GenesisStates::APPROVED;
                break;
            case GenesisStates::ERROR:
                return GenesisStates::ERROR;
                break;
            case GenesisStates::DECLINED:
                return GenesisStates::DECLINED;
                break;
            default:
                return GenesisStates::NEW_STATUS;
        }
    }

    /**
     * Order transaction types with Card Transaction types in front
     *
     * @param array $selectedTypes Selected transaction types
     *
     * @return array
     */
    protected function orderCardTransactionTypes($selectedTypes)
    {
        $creditCardTypes = GenesisTypes::getCardTransactionTypes();

        asort($selectedTypes);

        $sortedArray = array_intersect($creditCardTypes, $selectedTypes);

        return array_merge(
            $sortedArray,
            array_diff($selectedTypes, $sortedArray)
        );
    }

    /**
     * @param $transactionType
     * @return string
     */
    private function getCustomParameterKey($transactionType)
    {
        switch ($transactionType) {
            case GenesisTypes::PAY_PAL:
                $result = 'payment_type';
                break;
            case GenesisTypes::GOOGLE_PAY:
            case GenesisTypes::APPLE_PAY:
                $result = 'payment_subtype';
                break;
            default:
                $result = 'unknown';
        }

        return $result;
    }

    /**
     * Retrieve the SCA Exemption defined Amount in the method configuration
     *
     * @return float
     */
    private function getScaExemptionAmount()
    {
        $scaAmount = (float) $this->getMethodConfig()[ConfigKey::CHECKOUT_SCA_EXEMPTION_AMOUNT];

        if ($scaAmount < 0) {
            $scaAmount = 0.0;
        }

        return $scaAmount;
    }

    /**
     * Assign the Threeds Parameters to the Genesis Request object
     *
     * @return void
     * @throws \Exception
     */
    protected function populateThreedsParameters()
    {
        $this->threedsService->initializePayment($this->paymentData);

        /** @var GenesisWpfCreate $request */
        $request = $this->getGenesis()->request();

        // 3DSv2 Control Attributes
        $request->setThreedsV2ControlChallengeIndicator(
            $this->getMethodConfig()[ConfigKey::CHECKOUT_THREEDS_CHALLENGE_INDICATOR]
        );

        // 3DSv2 Purchase Attributes
        $request->setThreedsV2PurchaseCategory(
            $this->paymentData->hasPhysicalItems() ? Categories::GOODS : Categories::SERVICE
        );

        // 3DSv2 Risk Attributes
        $request->setThreedsV2MerchantRiskShippingIndicator(
            $this->threedsService->fetchShippingIndicator()
        );
        $request->setThreedsV2MerchantRiskDeliveryTimeframe(
            $this->paymentData->hasPhysicalItems() ?
                DeliveryTimeframes::ANOTHER_DAY : DeliveryTimeframes::ELECTRONICS
        );
        $request->setThreedsV2MerchantRiskReorderItemsIndicator($this->threedsService->fetchReorderItemsIndicator());

        // Account Holder Attributes
        if (!$this->threedsService->isGuestCheckout()) {
            $request->setThreedsV2CardHolderAccountCreationDate($this->threedsService->getCustomerDateCreatedAt());

            $request->setThreedsV2CardHolderAccountUpdateIndicator($this->threedsService->fetchUpdateIndicator());
            $request->setThreedsV2CardHolderAccountLastChangeDate(
                $this->threedsService->getCustomerModificationDate()->format(ThreedsService::DATE_TIME)
            );

            $request->setThreedsV2CardHolderAccountPasswordChangeDate(
                $this->threedsService->getCustomerDateUpdatedAt()
            );
            $request->setThreedsV2CardHolderAccountPasswordChangeIndicator(
                $this->threedsService->fetchPasswordChangeIndicator()
            );

            $request->setThreedsV2CardHolderAccountShippingAddressUsageIndicator(
                $this->threedsService->fetchShippingUsageIndicator()
            );
            $request->setThreedsV2CardHolderAccountShippingAddressDateFirstUsed(
                $this->threedsService->getShippingAddressCreationDate() !== null ?
                    $this->threedsService->getShippingAddressCreationDate()->format(ThreedsService::DATE_TIME) : null
            );

            $request->setThreedsV2CardHolderAccountTransactionsActivityLast24Hours(
                $this->threedsService->getTransactionActivityLast24Hours()
            );
            $request->setThreedsV2CardHolderAccountTransactionsActivityPreviousYear(
                $this->threedsService->getTransctionActivityPreviousYear()
            );
            $request->setThreedsV2CardHolderAccountPurchasesCountLast6Months(
                $this->threedsService->getPaidOrdersLast6Months()
            );

            $request->setThreedsV2CardHolderAccountRegistrationDate(
                $this->threedsService->getFirstOrderDate()->format(ThreedsService::DATE_TIME)
            );
        }

        $request->setThreedsV2CardHolderAccountRegistrationIndicator(
            $this->threedsService->fetchRegistrationIndicator()
        );
    }
}
