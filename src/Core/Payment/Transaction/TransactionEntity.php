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

namespace Emerchantpay\Genesis\Core\Payment\Transaction;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class TransactionEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $transaction_id;

    /**
     * @var string|null
     */
    protected $unique_id;

    /**
     * @var string|null
     */
    protected $reference_id;

    /**
     * @var string
     */
    protected $payment_method;

    /**
     * @var string|null
     */
    protected $terminal_token;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string|null
     */
    protected $order_id;

    /**
     * @var string|null
     */
    protected $transaction_type;

    /**
     * @var int
     */
    protected $amount;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var string|null
     */
    protected $message;

    /**
     * @var string|null
     */
    protected $technical_message;

    /**
     * @var string|null
     */
    protected $request;

    /**
     * @var string|null
     */
    protected $response;

    /**
     * @var string|null
     */
    protected $shopware_payment_id;

    public function getTransactionId(): string
    {
        return $this->transaction_id;
    }

    public function setTransactionId(string $transaction_id): void
    {
        $this->transaction_id = $transaction_id;
    }

    public function getUniqueId(): ?string
    {
        return $this->unique_id;
    }

    public function setUniqueId(string $unique_id): void
    {
        $this->unique_id = $unique_id;
    }

    public function getReferenceId(): ?string
    {
        return $this->reference_id;
    }

    public function setReferenceId(string $reference_id): void
    {
        $this->reference_id = $reference_id;
    }

    public function getPaymentMethod(): string
    {
        return $this->payment_method;
    }

    public function setPaymentMethod(string $payment_method): void
    {
        $this->payment_method = $payment_method;
    }

    public function getTerminalToken(): ?string
    {
        return $this->terminal_token;
    }

    public function setTerminalToken(string $terminal_token): void
    {
        $this->terminal_token = $terminal_token;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getOrderId(): ?string
    {
        return $this->order_id;
    }

    public function setOrderId(string $order_id): void
    {
        $this->order_id = $order_id;
    }

    public function getTransactionType(): ?string
    {
        return $this->transaction_type;
    }

    public function setTransactionType(string $transaction_type): void
    {
        $this->transaction_type = $transaction_type;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getTechnicalMessage(): ?string
    {
        return $this->technical_message;
    }

    public function setTechnicalMessage(string $technical_message): void
    {
        $this->technical_message = $technical_message;
    }

    public function getRequest(): ?string
    {
        return $this->request;
    }

    public function setRequest(string $request): void
    {
        $this->request = $request;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(string $response): void
    {
        $this->response = $response;
    }

    public function getShopwarePaymentId(): ?string
    {
        return $this->shopware_payment_id;
    }

    public function setShopwarePaymentId(string $shopware_payment_id): void
    {
        $this->shopware_payment_id = $shopware_payment_id;
    }
}
