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

namespace Emerchantpay\Genesis\Storefront\Controller;

use Emerchantpay\Genesis\Service\Payment\Checkout;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GenesisIpnController extends StorefrontController
{
    /**
     * @var Checkout
     */
    private $checkoutService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ContainerInterface $container,
        Checkout $checkoutService,
        LoggerInterface $logger
    ) {
        $this->container = $container;
        $this->checkoutService = $checkoutService;
        $this->logger = $logger;
    }

    /**
     * @Route(
     *     "/emerchantpay/ipn/checkout",
     *     name="frontend.emerchantpay.ipn.checkout",
     *     options={"seo"="false"},
     *     methods={"POST"},
     *     defaults={"auth_required"=false, "csrf_protected"=false, "_routeScope"={"storefront"}}
     * )
     */
    public function processCheckoutNotification(Request $request, SalesChannelContext $context): Response
    {
        try {
            $this->logger->info('Notification Received.', $request->request->all());

            $this->checkoutService->prepareSdkNotificationObject($request->request->all());
            $this->checkoutService->executeReconciliation();
            $this->checkoutService->processNotification($context->getContext());

            $notification = $this->checkoutService->getNotification();

            return new Response(
                $notification->generateResponse(),
                Response::HTTP_OK,
                ['Content-Type: application/xml']
            );
        } catch (\Exception $error) {
            $message = sprintf(
                'Error during notification process (emerchantpay %s): %s',
                $this->checkoutService->getMethod(),
                $error->getMessage()
            );
            $this->logger->error($message, [0 => $error->getTraceAsString()]);

            return new Response(
                $error->getMessage() . PHP_EOL . $error->getTraceAsString(),
                Response::HTTP_BAD_REQUEST,
                ['Content-Type: text/plain']
            );
        }
    }
}
