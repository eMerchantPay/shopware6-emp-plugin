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

use Emerchantpay\Genesis\Core\Payment\Transaction\TransactionEntity;
use Emerchantpay\Genesis\Utils\Data\PaymentData;
use Emerchantpay\Genesis\Utils\Data\ReferenceData;
use Genesis\Api\Notification;
use Genesis\Genesis;
use Genesis\Utils\Currency as GenesisCurrency;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;

class Transaction
{
    /**
     * @var EntityRepository
     */
    private $transactionRepository;

    public function __construct(EntityRepository $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Add new Transaction Row
     */
    public function addTransactionFromRequest(
        PaymentData $payment,
        Genesis $genesis,
        string $mode,
        string $method
    ): void {
        /** @var \stdClass $responseObject */
        $responseObject = $genesis->response()->getResponseObject();

        $this->transactionRepository->create([
            [
                'id'
                => Uuid::randomHex(),
                'transaction_id'      => $payment->getTransactionId(),
                'unique_id'           => isset($responseObject->unique_id) ? $responseObject->unique_id : null,
                'reference_id'        => isset($responseObject->reference_id) ? $responseObject->reference_id : null,
                'payment_method'      => $method,
                'terminal_token'      => isset($responseObject->terminal_token) ?
                    $responseObject->terminal_token : null,
                'order_id'            => $payment->getOrderId(),
                'transaction_type'    => isset($responseObject->transaction_type) ?
                    $responseObject->transaction_type : Checkout::CHECKOUT_TRANSACTION_TYPE,
                'status'              => isset($responseObject->status) ? $responseObject->status : null,
                'amount'              => isset($responseObject->amount) ? (int) GenesisCurrency::amountToExponent(
                    $responseObject->amount,
                    $responseObject->currency
                ) : null,
                'currency'            => isset($responseObject->currency) ? $responseObject->currency : null,
                'mode'                => $mode,
                'message'             => isset($responseObject->message) ? $responseObject->message : null,
                'technical_message'   => isset($responseObject->technical_message) ?
                    $responseObject->technical_message : null,
                'request'             => serialize([0 => $payment->toArray()]),
                'response'            => serialize([0 => (array) $responseObject]),
                'shopware_payment_id' => $payment->getShopwarePaymentId()
            ],
        ], Context::createDefaultContext());
    }

    /**
     * Add new transaction row
     */
    public function addReferenceTransactionFromRequest(
        ReferenceData $referenceData,
        Genesis $genesis,
        string $mode,
        string $method
    ) {
        /** @var \stdClass $responseObject */
        $responseObject = $genesis->response()->getResponseObject();

        $this->transactionRepository->create([
            [
                'id' => Uuid::randomHex(),
                'transaction_id'      => $referenceData->getTransactionId(),
                'unique_id'           => isset($responseObject->unique_id) ? $responseObject->unique_id : null,
                'reference_id'        => $genesis->request()->getReferenceId(),
                'payment_method'      => $method,
                'terminal_token'      => $referenceData->getTerminalToken(),
                'order_id'            => $referenceData->getOrderId(),
                'transaction_type'    => isset($responseObject->transaction_type) ?
                    $responseObject->transaction_type : 'undefined',
                'status'              => isset($responseObject->status) ? $responseObject->status : null,
                'amount'              => isset($responseObject->amount) ? (int) GenesisCurrency::amountToExponent(
                    $responseObject->amount,
                    $responseObject->currency
                ) : 0,
                'currency'            => isset($responseObject->currency) ?
                    $responseObject->currency : $referenceData->getCurrency(),
                'mode'                => $mode,
                'message'             => isset($responseObject->message) ? $responseObject->message : null,
                'technical_message'   => isset($responseObject->technical_message) ?
                    $responseObject->technical_message : null,
                'request'             => serialize([0 => $referenceData->toArray()]),
                'response'            => serialize([0 => (array) $responseObject]),
                'shopware_payment_id' => $referenceData->getShopwarePaymentId(),
            ]
        ], Context::createDefaultContext());
    }

    /**
     * Add Transaction from Notification logic
     */
    public function addTransactionFromNotification(
        TransactionEntity $transactionEntity,
        $payment,
        Notification $notification
    ): void {
        $this->transactionRepository->create([
            [
                'id'                  => Uuid::randomHex(),
                'transaction_id'      => $transactionEntity->getTransactionId(),
                'unique_id'           => $payment->unique_id,
                'reference_id'        => $transactionEntity->getUniqueId(),
                'payment_method'      => $transactionEntity->getPaymentMethod(),
                'terminal_token'      => isset($payment->terminal_token) ? $payment->terminal_token : null,
                'order_id'            => $transactionEntity->getOrderId(),
                'transaction_type'    => $payment->transaction_type,
                'status'              => $payment->status,
                'amount'              => isset($payment->amount) ? (int) GenesisCurrency::amountToExponent(
                    $payment->amount,
                    $payment->currency
                ) : null,
                'currency'            => isset($payment->currency) ? $payment->currency : null,
                'mode'                => $transactionEntity->getMode(),
                'message'             => isset($payment->message) ? $payment->message : null,
                'technical_message'   => isset($payment->technical_message) ? $payment->technical_message : null,
                'request'             => serialize([0 => (array) $notification->getReconciliationObject()]),
                'response'            => serialize([0 => $notification->getNotificationObject()->getArrayCopy()]),
                'shopware_payment_id' => $transactionEntity->getShopwarePaymentId()
            ],
        ], Context::createDefaultContext());
    }

    /**
     * Update Transaction from Notification logic
     */
    public function updateTransactionFromNotification(
        TransactionEntity $transactionEntity,
        $payment,
        Notification $notification
    ): void {
        $request = unserialize($transactionEntity->getRequest());
        $request[] = (array) $payment;

        $response = unserialize($transactionEntity->getResponse());
        $response[] = (array) $notification->getReconciliationObject();

        $this->transactionRepository->update([
            [
                'id'                => $transactionEntity->getId(),
                'status'            => $payment->status,
                'message'           => isset($payment->message) ?
                    $transactionEntity->getMessage() . PHP_EOL . $payment->message : $transactionEntity->getMessage(),
                'technical_message' => isset($payment->technical_message) ?
                    $transactionEntity->getTechnicalMessage() . PHP_EOL . $payment->technical_message :
                    $transactionEntity->getTechnicalMessage(),
                'request'           => serialize($request),
                'response'          => serialize($response),
            ],
        ], Context::createDefaultContext());
    }

    public function findWebPayment(string $unique_id): ?TransactionEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('unique_id', $unique_id));
        $criteria->addFilter(new EqualsFilter('transaction_type', Checkout::CHECKOUT_TRANSACTION_TYPE));

        /** @var EntitySearchResult $result */
        $result = $this->transactionRepository->search($criteria, Context::createDefaultContext());

        if ($result->count() === 0) {
            return null;
        }

        return $result->first();
    }

    public function findPaymentTransaction(string $unique_id): ?TransactionEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('unique_id', $unique_id));
        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [
                    new EqualsFilter('transaction_type', Checkout::CHECKOUT_TRANSACTION_TYPE)
                ]
            )
        );

        /** @var EntitySearchResult $result */
        $result = $this->transactionRepository->search($criteria, Context::createDefaultContext());

        if ($result->count() === 0) {
            return null;
        }

        return $result->first();
    }

    public function findTransactionsByOrder(string $order_id): EntitySearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('order_id', $order_id));

        return $this->transactionRepository->search($criteria, Context::createDefaultContext());
    }

    public function findPaymentReference(string $reference_id): ?TransactionEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('reference_id', $reference_id));
        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [
                    new EqualsFilter('transaction_type', Checkout::CHECKOUT_TRANSACTION_TYPE)
                ]
            )
        );

        /** @var EntitySearchResult $result */
        $result = $this->transactionRepository->search($criteria, Context::createDefaultContext());

        if ($result->count() === 0) {
            return null;
        }

        return $result->first();
    }
}
