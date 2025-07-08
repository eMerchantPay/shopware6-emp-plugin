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

namespace Emerchantpay\Genesis\Storefront\Controller;

use Emerchantpay\Genesis\Service\Flow\ReturnUrl;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class GenesisReturnController extends StorefrontController
{
    /**
     * @var ReturnUrl
     */
    private ReturnUrl $returnUrlService;

    public function __construct(ContainerInterface $container, ReturnUrl $return_url)
    {
        $this->container = $container;
        $this->returnUrlService = $return_url;
    }

    /**
     * Redirect handler Controller action
     */
    #[Route(
        path:     '/emerchantpay/return/{token}',
        name:     'frontend.emerchantpay.return',
        options:  ['seo' => 'false'],
        defaults: ['auth_required' => false, 'csrf_protected' => false, '_routeScope' => ['storefront']],
        methods:  ['GET']
    )]
    public function redirectOrderEndpoint(string $token): RedirectResponse
    {
        return $this->redirect($this->returnUrlService->getOrderEndpoint($token));
    }

    /**
     * If the payment is processed into an iframe, response with a JS, breaking that iframe jail
     *
     * @param string $token
     *
     * @return Response
     */
    #[Route(
        path:     '/emerchantpay/return-iframe/{token}',
        name:     'frontend.emerchantpay.return_iframe',
        options:  ['seo' => 'false'],
        defaults: ['auth_required' => false, 'csrf_protected' => false, '_routeScope' => ['storefront']],
        methods:  ['GET']
    )]
    public function redirectOrderEndpointBreakIframe(string $token): Response
    {
        $response = $this->render(
            'storefront/iframehandler/index.html.twig',
            ['returnUrl' => $this->returnUrlService->getOrderEndpoint($token)]
        );
        // In case we drop the support for 6.5
        $response->headers->set('x-frame-options', 'ALLOWALL', true);

        return $response;
    }
}
