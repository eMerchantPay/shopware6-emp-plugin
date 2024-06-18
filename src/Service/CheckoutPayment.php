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

namespace Emerchantpay\Genesis\Service;

use Emerchantpay\Genesis\Constants\ReturnUrl;
use Emerchantpay\Genesis\Service\Payment\Checkout;
use Emerchantpay\Genesis\Utils\Config;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CheckoutPayment
 */
class CheckoutPayment implements AsynchronousPaymentHandlerInterface
{
    /**
     * @var OrderTransactionStateHandler
     */
    private $transactionStateHandler;

    /**
     * @var Config
     */
    private $configService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Checkout
     */
    private $paymentService;

    public function __construct(
        OrderTransactionStateHandler $transactionStateHandler,
        Config $config,
        LoggerInterface $logger,
        Checkout $paymentService
    ) {
        $this->logger = $logger;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->configService = $config;
        $this->paymentService = $paymentService;
    }

    /**
     * Web Payment Form creation
     *
     * @throws PaymentException
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        // Method that sends the return URL to the external gateway and gets a redirect URL back
        try {
            $redirectUrl = $this->sendReturnUrlToExternalGateway($transaction, $salesChannelContext->getContext());
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransaction()->getId(),
                $e->getMessage()
            );
        }

        // Redirect to external gateway
        return new RedirectResponse($redirectUrl);
    }

    /**
     * Payment finalization
     * Doesn't update Order or Payment statuses, the statuses are handled upon Gateway notification
     *
     * @param AsyncPaymentTransactionStruct $transaction
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     *
     * @throws PaymentException
     * @return void
     */
    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): void {
        $status        = $request->query->getString('status');
        $transactionId = $transaction->getOrderTransaction()->getId();

        // Order Payment Status will be updated upon received Notification
        $this->logger->debug(
            sprintf(
                'Customer returned with status %s. Transaction Id: %s. Order Id: %s',
                ucfirst($status),
                $transactionId,
                $transaction->getOrderTransaction()->getOrderId()
            )
        );

        switch ($status) {
            case ReturnUrl::FAILURE:
                $message = 'An error appeared during your payment.';

                throw PaymentException::asyncFinalizeInterrupted($transactionId, $message);
            case ReturnUrl::CANCEL:
                $message = 'Customer canceled the payment on the Emerchantpay page.';

                throw PaymentException::customerCanceled($transactionId, $message);
        }
    }

    /**
     * Load Genesis Request attributes
     *
     * @throws \Exception
     */
    private function sendReturnUrlToExternalGateway(
        AsyncPaymentTransactionStruct $transaction,
        Context $context
    ): string {
        $this->paymentService->prepareSdkInitialTransactionRequest()
            ->setInitialRequestProperties($transaction, $context);
        $responseObject = $this->paymentService->executeTransactionRequest();

        return $responseObject->redirect_url;
    }
}
