<?php

declare(strict_types=1);

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
use Emerchantpay\Genesis\Core\Payment\Transaction\TransactionEntity;
use Emerchantpay\Genesis\Service\Payment\Exceptions\InvalidGenesisRequest;
use Emerchantpay\Genesis\Service\Payment\Exceptions\InvalidRequestAttributes;
use Emerchantpay\Genesis\Utils\Config;
use Emerchantpay\Genesis\Utils\ReferenceTransactions;
use Genesis\API\Constants\Endpoints;
use Genesis\API\Constants\Environments;
use Genesis\API\Constants\Payment\Methods as GenesisPproMethods;
use Genesis\API\Constants\Transaction\States as GenesisStates;
use Genesis\API\Constants\Transaction\Types as GenesisTypes;
use Genesis\API\Request\WPF\Create as GenesisWpfCreate;
use Genesis\Config as GenesisConfig;
use Genesis\Exceptions\ErrorParameter as GenesisErrorParameter;
use Genesis\Exceptions\Exception as GenesisException;
use Genesis\Genesis;
use Genesis\Utils\Currency;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

class Checkout extends Base
{
    public const CHECKOUT_TRANSACTION_TYPE = 'web_payment';

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
     * @throws \Genesis\Exceptions\ErrorAPI
     * @throws \Genesis\Exceptions\ErrorParameter
     * @throws \Genesis\Exceptions\InvalidArgument
     * @throws \Genesis\Exceptions\InvalidClassMethod
     * @throws \Genesis\Exceptions\InvalidResponse
     * @throws \Exception
     */
    public function executeTransactionRequest(): \stdClass
    {
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

            if ($responseObject->status !== GenesisStates::NEW_STATUS) {
                $message = isset($responseObject->message) ? $responseObject->message : 'Unknown decline reason';
                $this->getLogger()->info(
                    $message,
                    (array) $responseObject
                );

                throw new InvalidGenesisRequest($message);
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
     * @throws InvalidGenesisRequest
     */
    public function executeReferenceRequest(): \stdClass
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

            if (!in_array($responseObject->status, [GenesisStates::APPROVED, GenesisStates::PENDING_ASYNC])) {
                $message = isset($responseObject->message) ? $responseObject->message : 'Unknown decline reason';
                $this->getLogger()->info(
                    $message,
                    (array) $responseObject
                );

                throw new InvalidGenesisRequest($message);
            }

            return $genesis->response()->getResponseObject();
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
        \stdClass $responseObject,
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

        if (
            $responseObject->status === GenesisStates::APPROVED ||
            $responseObject->status === GenesisStates::PENDING_ASYNC
        ) {
            $this->updatePaymentState(
                $transaction->getShopwarePaymentId(),
                $this->getPaymentStatusByAction($action),
                $context,
                $responseObject->transaction_type
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
        if ($transactionType === GenesisTypes::GOOGLE_PAY) {
            return in_array(
                ConfigKey::GOOGLE_PAY_TRANSACTION_PREFIX . ConfigKey::GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE,
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
            $this->getGenesis()->request()
                ->setTransactionId($this->paymentData->getTransactionId())
                ->setAmount($this->paymentData->getAmount())
                ->setCurrency($this->paymentData->getCurrency())
                ->setUsage("Payment via {$this->getShopName()}")
                ->setDescription($this->paymentData->buildOrderDescriptionText())

                ->setCustomerEmail($this->paymentData->getEmail())
                ->setCustomerPhone($this->paymentData->getPhone())

                ->setReturnSuccessUrl($this->paymentData->getSuccessUrl())
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
            return new Genesis('WPF\Create');
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

        $selectedTypes = $this->getMethodConfig()[ConfigKey::CHECKOUT_TRANSACTION_TYPES];
        $pproSuffix = ConfigKey::PPRO_TRANSACTION_SUFFIX;
        $methods = GenesisPproMethods::getMethods();

        foreach ($methods as $method) {
            $aliasMap[$method . $pproSuffix] = GenesisTypes::PPRO;
        }

        $aliasMap = array_merge($aliasMap, [
            ConfigKey::GOOGLE_PAY_TRANSACTION_PREFIX . ConfigKey::GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE =>
                GenesisTypes::GOOGLE_PAY,
            ConfigKey::GOOGLE_PAY_TRANSACTION_PREFIX . ConfigKey::GOOGLE_PAY_PAYMENT_TYPE_SALE      =>
                GenesisTypes::GOOGLE_PAY
        ]);

        foreach ($selectedTypes as $selectedType) {
            if (!array_key_exists($selectedType, $aliasMap)) {
                $processedList[] = $selectedType;

                continue;
            }

            $transactionType = $aliasMap[$selectedType];

            $processedList[$transactionType]['name'] = $transactionType;

            $key = GenesisTypes::GOOGLE_PAY === $transactionType ? 'payment_type' : 'payment_method';

            $processedList[$transactionType]['parameters'][] = [
                $key => str_replace([$pproSuffix, ConfigKey::GOOGLE_PAY_TRANSACTION_PREFIX], '', $selectedType),
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
}
