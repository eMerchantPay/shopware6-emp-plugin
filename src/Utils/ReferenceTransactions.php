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

namespace Emerchantpay\Genesis\Utils;

use Emerchantpay\Genesis\Constants\Config as ConfigKeys;
use Emerchantpay\Genesis\Core\Payment\Transaction\TransactionEntity;
use Emerchantpay\Genesis\Service\Payment\Checkout;
use Emerchantpay\Genesis\Service\Payment\Transaction;
use Emerchantpay\Genesis\Utils\Mappers\Exceptions\InvalidReferenceData;
use Genesis\Api\Constants\Transaction\Names;
use Genesis\Api\Constants\Transaction\States;
use Genesis\Api\Constants\Transaction\Types;
use Genesis\Utils\Currency;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

if (file_exists(dirname(dirname(__DIR__)) . '/vendor/autoload.php')) {
    $loader = require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
    if ($loader !== true) {
        spl_autoload_unregister([$loader, 'loadClass']);
        $loader->register(false);
    }
}

/**
 * Class ReferenceTransactions
 * @package Emerchantpay\Genesis\Utils
 */
final class ReferenceTransactions
{
    public const KEY_CAN_VOID = 'canVoid';
    public const KEY_CAN_CAPTURE = 'canCapture';
    public const KEY_CAN_REFUND = 'canRefund';
    public const KEY_ACTION_TRANSACTION = 'actionTransaction';
    public const KEY_INITIAL_TRANSACTION = 'initialTransaction';
    public const KEY_CAPTURE_TRANSACTION = 'captureTransaction';
    public const KEY_REFUND_TRANSACTION = 'refundTransaction';
    public const KEY_VOID_TRANSACTION = 'voidTransaction';

    public const ACTION_VOID = 'void';
    public const ACTION_CAPTURE = 'capture';
    public const ACTION_REFUND = 'refund';

    /**
     * @var Transaction
     */
    private $transactionService;

    /**
     * @var TransactionTree
     */
    private $transactionTreeService;

    /**
     * @var string
     */
    private $uniqueId;

    /**
     * @var string
     */
    private $orderId;

    /**
     * @var array
     */
    private $transactionTree;

    /**
     * @var TransactionEntity
     */
    private $initialPayment;

    /**
     * @var TransactionEntity
     */
    private $actionTransaction = null;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Transaction $transactionService,
        TransactionTree $transactionTreeService,
        Config $config
    ) {
        $this->transactionService     = $transactionService;
        $this->transactionTreeService = $transactionTreeService;
        $this->config                 = $config;
    }

    /**
     * Retrieve the available Reference actions for the given Payment
     *
     * @throws \Exception
     */
    public function getReferenceDetails(string $orderId, string $uniqueId)
    {
        $this->init($orderId, $uniqueId);

        return array_merge(
            $this->getDefaultOutputData(),
            $this->getAllowedActions(),
            $this->getActionTransaction(),
            $this->getInitialTransaction(),
            $this->getRefundTransaction(),
            $this->getVoidTransaction(),
            $this->getCaptureTransaction()
        );
    }

    public function getDefaultOutputData(): array
    {
        return [
            self::KEY_CAN_VOID => false,
            self::KEY_CAN_CAPTURE => false,
            self::KEY_CAN_REFUND => false,
            self::KEY_INITIAL_TRANSACTION => null,
            self::KEY_ACTION_TRANSACTION => null,
            self::KEY_CAPTURE_TRANSACTION => null,
            self::KEY_REFUND_TRANSACTION => null,
            self::KEY_VOID_TRANSACTION => null
        ];
    }

    /**
     * Load the Transaction that request will Reference to
     */
    public function loadInitialTransaction(string $uniqueId): ?TransactionEntity
    {
        $transaction = $this->transactionService->findPaymentTransaction($uniqueId);

        if ($transaction === null) {
            throw new InvalidReferenceData('Initial Transaction not found');
        }

        return $transaction;
    }

    /**
     * @throws \Exception
     */
    private function init($orderId, $uniqueId)
    {
        $this->setOrderId($orderId);
        $this->setUniqueId($uniqueId);
        $this->loadInitialPayment();
        $this->loadTransactionTree();
    }

    private function setUniqueId(string $uniqueId): void
    {
        $this->uniqueId = $uniqueId;
    }

    private function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
    }

    /**
     * @throws \Exception
     */
    private function getAllowedActions()
    {
        $lastMeaningfulTransaction = $this->transactionTreeService->findLastApprovedLeaf(
            $this->transactionTree,
            $this->uniqueId
        );

        /** @var TransactionEntity $actionTransaction */
        $this->actionTransaction = clone $this->initialPayment;
        if ($this->initialPayment->getUniqueId() !== $lastMeaningfulTransaction[TransactionTree::DATA_UNIQUE_ID]) {
            $referenceTransaction = $this->transactionService->findPaymentTransaction(
                $lastMeaningfulTransaction[TransactionTree::DATA_UNIQUE_ID]
            );
        }

        if (isset($referenceTransaction) && $referenceTransaction) {
            unset($actionTransaction);
            $this->actionTransaction = clone $referenceTransaction;
            unset($referenceTransaction);
        }

        $approvedState = $this->actionTransaction->getStatus() === States::APPROVED;

        return [
            self::KEY_CAN_VOID => $approvedState && $this->isValidAction(
                self::ACTION_VOID,
                $this->actionTransaction->getTransactionType()
            ),
            self::KEY_CAN_CAPTURE => $approvedState && $this->isValidAction(
                self::ACTION_CAPTURE,
                $this->actionTransaction->getTransactionType()
            ),

            self::KEY_CAN_REFUND => $approvedState && $this->isValidAction(
                self::ACTION_REFUND,
                $this->actionTransaction->getTransactionType()
            )
        ];
    }

    /**
     * Load the Initial transaction
     */
    private function getInitialTransaction(): array
    {
        $transaction = $this->transactionTreeService->findInitialTransaction(
            $this->transactionTree,
            Checkout::CHECKOUT_TRANSACTION_TYPE
        );

        if (!empty($transaction)) {
            $transaction = $this->parseTransactionAttributes($transaction);
        }

        return [
            self::KEY_INITIAL_TRANSACTION => $transaction
        ];
    }

    /**
     * Load Reference Refund Transaction
     */
    private function getRefundTransaction(): array
    {
        $transaction = $this->transactionTreeService->findReferenceTransaction(
            $this->transactionTree,
            [States::APPROVED, States::REFUNDED],
            [Types::REFUND, Types::SDD_REFUND, Types::KLARNA_REFUND]
        );

        if (!empty($transaction)) {
            $transaction = $this->parseTransactionAttributes($transaction);
        }

        return [
            self::KEY_REFUND_TRANSACTION => $transaction
        ];
    }

    /**
     * Load Reference Void Transaction
     */
    private function getVoidTransaction(): array
    {
        $transaction = $this->transactionTreeService->findReferenceTransaction(
            $this->transactionTree,
            [States::APPROVED, States::VOIDED],
            [Types::VOID]
        );

        if (!empty($transaction)) {
            $transaction = $this->parseTransactionAttributes($transaction);
        }

        return [
            self::KEY_VOID_TRANSACTION => $transaction
        ];
    }

    /**
     * Load Reference Void Transaction
     */
    private function getCaptureTransaction(): array
    {
        $transaction = $this->transactionTreeService->findReferenceTransaction(
            $this->transactionTree,
            [States::APPROVED],
            [Types::CAPTURE, Types::KLARNA_CAPTURE]
        );

        if (!empty($transaction)) {
            $transaction = $this->parseTransactionAttributes($transaction);
        }

        return [
            self::KEY_CAPTURE_TRANSACTION => $transaction
        ];
    }

    private function getActionTransaction(): array
    {
        $data = [];
        $default = [
            TransactionTree::DATA_UNIQUE_ID => null,
            TransactionTree::DATA_AMOUNT => null,
            TransactionTree::DATA_CURRENCY => null,
            TransactionTree::DATA_TRANSACTION_TYPE => null
        ];

        if ($this->actionTransaction) {
            $data = [
                TransactionTree::DATA_UNIQUE_ID => $this->actionTransaction->getUniqueId(),
                TransactionTree::DATA_AMOUNT => $this->actionTransaction->getAmount(),
                TransactionTree::DATA_CURRENCY => $this->actionTransaction->getCurrency(),
                TransactionTree::DATA_TRANSACTION_TYPE => $this->actionTransaction->getTransactionType()
            ];

            $data = $this->parseTransactionAttributes($data);
        }

        return [
            self::KEY_ACTION_TRANSACTION => array_merge(
                $default,
                $data
            )
        ];
    }

    private function loadTransactionTree()
    {
        /** @var EntitySearchResult $transactions */
        $transactions = $this->transactionService->findTransactionsByOrder($this->orderId);

        $this->transactionTree = $this->transactionTreeService->buildTree(
            $this->uniqueId,
            $transactions->getElements()
        );
    }

    /**
     * @throws \Exception
     */
    private function loadInitialPayment()
    {
        $this->initialPayment = $this->transactionService->findWebPayment($this->uniqueId);

        if (!$this->initialPayment) {
            throw new \Exception('Invalid transaction identifier');
        }

        if ($this->initialPayment->getOrderId() !== $this->orderId) {
            throw new \Exception('Invalid payment identifier');
        }
    }

    /**
     * Validate the Request action
     *
     * @throws \Exception
     */
    private function isValidAction(string $action, string $transactionType): bool
    {
        if ($this->isTransactionWithCustomAttribute($transactionType)) {
            return $this->isCustomAttributeBasedTransactionSelected($action, $transactionType);
        }

        switch ($action) {
            case self::ACTION_CAPTURE:
                return Types::canCapture($transactionType);
                break;
            case self::ACTION_REFUND:
                return Types::canRefund($transactionType);
                break;
            case self::ACTION_VOID:
                return Types::canVoid($transactionType);
                break;
            default:
                throw new \Exception('Invalid Reference action given');
        }
    }

    private function parseTransactionAttributes($transaction)
    {
        $transaction[TransactionTree::DATA_AMOUNT] = Currency::exponentToAmount(
            $transaction[TransactionTree::DATA_AMOUNT],
            $transaction[TransactionTree::DATA_CURRENCY]
        );
        $transaction[TransactionTree::DATA_TRANSACTION_TYPE] = Names::getName(
            $transaction[TransactionTree::DATA_TRANSACTION_TYPE]
        );

        return $transaction;
    }

    /**
     * Check if special validation should be applied
     *
     * @param $transactionType
     * @return bool
     */
    private function isTransactionWithCustomAttribute($transactionType): bool
    {
        $transactionTypes = [
            Types::GOOGLE_PAY,
            Types::PAY_PAL,
            Types::APPLE_PAY,
        ];

        return in_array($transactionType, $transactionTypes);
    }

    /**
     * Check if canCapture, canRefund, canVoid based on the selected custom attribute
     *
     * @param $action
     * @param $transactionType
     * @return bool
     */
    private function isCustomAttributeBasedTransactionSelected($action, $transactionType)
    {
        switch ($transactionType) {
            case Types::GOOGLE_PAY:
                if (self::ACTION_CAPTURE === $action || self::ACTION_VOID === $action) {
                    return in_array(
                        ConfigKeys::GOOGLE_PAY_TRANSACTION_PREFIX .
                        ConfigKeys::GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE,
                        $this->getCheckoutTransactionTypes()
                    );
                }

                if ($action === self::ACTION_REFUND) {
                    return in_array(
                        ConfigKeys::GOOGLE_PAY_TRANSACTION_PREFIX .
                        ConfigKeys::GOOGLE_PAY_PAYMENT_TYPE_SALE,
                        $this->getCheckoutTransactionTypes()
                    );
                }
                break;
            case Types::PAY_PAL:
                if (self::ACTION_CAPTURE === $action || self::ACTION_VOID === $action) {
                    return in_array(
                        ConfigKeys::PAYPAL_TRANSACTION_PREFIX . ConfigKeys::PAYPAL_PAYMENT_TYPE_AUTHORIZE,
                        $this->getCheckoutTransactionTypes()
                    );
                }

                if (self::ACTION_REFUND == $action) {
                    $refundableTypes = [
                        ConfigKeys::PAYPAL_TRANSACTION_PREFIX . ConfigKeys::PAYPAL_PAYMENT_TYPE_SALE,
                        ConfigKeys::PAYPAL_TRANSACTION_PREFIX . ConfigKeys::PAYPAL_PAYMENT_TYPE_EXPRESS,
                    ];

                    return (
                        count(
                            array_intersect(
                                $refundableTypes,
                                $this->getCheckoutTransactionTypes()
                            )
                        ) > 0
                    );
                }
                break;
            case Types::APPLE_PAY:
                if (self::ACTION_CAPTURE === $action || self::ACTION_VOID === $action) {
                    return in_array(
                        ConfigKeys::APPLE_PAY_TRANSACTION_PREFIX . ConfigKeys::APPLE_PAY_PAYMENT_TYPE_AUTHORIZE,
                        $this->getCheckoutTransactionTypes()
                    );
                }

                if (self::ACTION_REFUND == $action) {
                    return in_array(
                        ConfigKeys::APPLE_PAY_TRANSACTION_PREFIX . ConfigKeys::APPLE_PAY_PAYMENT_TYPE_SALE,
                        $this->getCheckoutTransactionTypes()
                    );
                }
                break;
            default:
                return false;
        } // end Switch
        return false;
    }

    /**
     * Get Selected Checkout Transaction Types
     */
    private function getCheckoutTransactionTypes(): array
    {
        return $this->config->getCheckout()[ConfigKeys::CHECKOUT_TRANSACTION_TYPES];
    }
}
