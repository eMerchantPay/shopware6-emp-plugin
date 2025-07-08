<?php declare(strict_types=1);
/*
 * Copyright (C) 2024 emerchantpay Ltd.
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
 * @copyright   2024 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace Emerchantpay\Genesis\Storefront\Controller;

use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class GenesisIframeController
 *
 * Returns the WPF URL for the iFrame
 *
 * @package Emerchantpay\Genesis\Storefront\Controller
 */
class GenesisIframeController extends StorefrontController
{

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns WPF URL
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route(
        path:     '/emerchantpay/iframe/url',
        name:     'emerchantpay.frontend.iframe.url',
        options:  ['seo'=> 'false'],
        defaults: ['auth_required' => false, 'csrf_protected' => false, '_routeScope' => ['storefront']],
        methods:  ['GET']
    )]
    public function iframeUrl(Request $request): JsonResponse
    {
        $url = $request->getSession()->get('redirectUrl', '');

        return new JsonResponse(['iframeUrl' => $url]);
    }
}
