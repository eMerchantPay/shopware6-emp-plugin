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

namespace Emerchantpay\Genesis\Api\Reference\Administration;

use Emerchantpay\Genesis\Service\Payment\Checkout;
use Emerchantpay\Genesis\Utils\ReferenceTransactions;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EmerchantpayGenesisTransactionController extends AbstractController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ReferenceTransactions
     */
    private $referenceUtilsService;

    /**
     * @var Checkout
     */
    private $checkoutService;

    public function __construct(
        ContainerInterface $container,
        LoggerInterface $logger,
        ReferenceTransactions $referenceService,
        Checkout $checkoutService
    ) {
        $this->container = $container;
        $this->logger = $logger;
        $this->referenceUtilsService = $referenceService;
        $this->checkoutService = $checkoutService;
    }

    /**
     * @Route(
     *     "/api/v{version}/emerchantpay-v1/genesis/transaction/payment-reference-details",
     *     name="api.emerchantpay.genesis.version-endpoint.transaction.payment-reference-details",
     *     methods={"POST"},
     *     defaults={"_routeScope"={"api"}}
     * )
     *
     * @Route(
     *     "/api/emerchantpay-v1/genesis/transaction/payment-reference-details",
     *     name="api.emerchantpay.genesis.transaction.payment-reference-details",
     *     methods={"POST"},
     *     defaults={"_routeScope"={"api"}}
     * )
     */
    public function buildReferenceDetails(Request $request, Context $context): JsonResponse
    {
        try {
            return new JsonResponse(
                $this->referenceUtilsService->getReferenceDetails(
                    $request->request->get('orderId'),
                    $request->request->get('uniqueId')
                )
            );
        } catch (\Exception $error) {
            $this->logger->error($error->getMessage(), $error->getTrace());

            return new JsonResponse(
                $this->referenceUtilsService->getDefaultOutputData()
            );
        }
    }

    /**
     * @Route(
     *     "/api/v{version}/_action/emerchantpay-v1/genesis/transaction/capture",
     *     name="api.emerchantpay.genesis.version-endpoint.transaction.capture",
     *     methods={"POST"},
     *     defaults={"_routeScope"={"api"}}
     * )
     *
     * @Route(
     *     "/api/_action/emerchantpay-v1/genesis/transaction/capture",
     *     name="api.emerchantpay.genesis.transaction.capture",
     *     methods={"POST"},
     *     defaults={"_routeScope"={"api"}}
     * )
     */
    public function doCapture(Request $request, Context $context): JsonResponse
    {
        try {
            $transaction = $this->referenceUtilsService->loadInitialTransaction($request->request->get('uniqueId'));

            $this->checkoutService->prepareSdkReferenceTransactionRequest(
                ReferenceTransactions::ACTION_CAPTURE,
                $transaction->getTransactionType(),
                $transaction->getTerminalToken()
            )->setReferenceRequestProperties($transaction, $request->getClientIp(), 'capture');
            $response = $this->checkoutService->executeReferenceRequest();

            $this->checkoutService->updateCapturePaymentState(
                $transaction,
                ReferenceTransactions::ACTION_CAPTURE,
                $response,
                $context
            );

            return new JsonResponse([
                'status' => 'success',
                'response' => $response
            ]);
        } catch (\Exception $error) {
            return new JsonResponse(
                [
                    'status' => "error",
                    'message' => $error->getMessage()
                ]
            );
        }
    }

    /**
     * @Route(
     *     "/api/v{version}/_action/emerchantpay-v1/genesis/transaction/refund",
     *     name="api.emerchantpay.genesis.version-endpoint.transaction.refund",
     *     methods={"POST"},
     *     defaults={"_routeScope"={"api"}}
     * )
     *
     * @Route(
     *     "/api/_action/emerchantpay-v1/genesis/transaction/refund",
     *     name="api.emerchantpay.genesis.transaction.refund",
     *     methods={"POST"},
     *     defaults={"_routeScope"={"api"}}
     * )
     */
    public function doRefund(Request $request, Context $context): JsonResponse
    {
        try {
            $transaction = $this->referenceUtilsService->loadInitialTransaction($request->request->get('uniqueId'));

            $this->checkoutService->prepareSdkReferenceTransactionRequest(
                ReferenceTransactions::ACTION_REFUND,
                $transaction->getTransactionType(),
                $transaction->getTerminalToken()
            )->setReferenceRequestProperties($transaction, $request->getClientIp(), 'refund');
            $response = $this->checkoutService->executeReferenceRequest();

            $this->checkoutService->updateCapturePaymentState(
                $transaction,
                ReferenceTransactions::ACTION_REFUND,
                $response,
                $context
            );

            return new JsonResponse([
                'status' => 'success',
                'response' => $response
            ]);
        } catch (\Exception $error) {
            return new JsonResponse(
                [
                    'status' => "error",
                    'message' => $error->getMessage()
                ]
            );
        }
    }

    /**
     * @Route(
     *     "/api/v{version}/_action/emerchantpay-v1/genesis/transaction/void",
     *     name="api.emerchantpay.genesis.version-endpoint.transaction.void",
     *     methods={"POST"},
     *     defaults={"_routeScope"={"api"}}
     * )
     *
     * @Route(
     *     "/api/_action/emerchantpay-v1/genesis/transaction/void",
     *     name="api.emerchantpay.genesis.transaction.void",
     *     methods={"POST"},
     *     defaults={"_routeScope"={"api"}}
     * )
     */
    public function doVoid(Request $request, Context $context): JsonResponse
    {
        try {
            $transaction = $this->referenceUtilsService->loadInitialTransaction($request->request->get('uniqueId'));

            $this->checkoutService->prepareSdkReferenceTransactionRequest(
                ReferenceTransactions::ACTION_VOID,
                $transaction->getTransactionType(),
                $transaction->getTerminalToken()
            )->setReferenceRequestProperties($transaction, $request->getClientIp(), 'void');
            $response = $this->checkoutService->executeReferenceRequest();

            $this->checkoutService->updateCapturePaymentState(
                $transaction,
                ReferenceTransactions::ACTION_VOID,
                $response,
                $context
            );

            return new JsonResponse([
                'status' => 'success',
                'response' => $response
            ]);
        } catch (\Exception $error) {
            return new JsonResponse(
                [
                    'status' => "error",
                    'message' => $error->getMessage()
                ]
            );
        }
    }
}
