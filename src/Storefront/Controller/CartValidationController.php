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

use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class CartValidationController
 *
 * Checks if cart is valid for this session
 *
 * @package Emerchantpay\Genesis\Storefront\Controller
 */
class CartValidationController extends StorefrontController
{
    /**
     * @var CartService
     */
    private CartService $cartService;

    /**
     * @param CartService        $cartService
     * @param ContainerInterface $container
     */
    public function __construct(CartService $cartService, ContainerInterface $container)
    {
        $this->cartService = $cartService;
        $this->container = $container;
    }

    /**
     * Checks if cart is valid for this session
     *
     * @param SalesChannelContext $context
     *
     * @return JsonResponse
     *
     */
    #[Route(
        path:     '/emerchantpay/cart/validate',
        name:     'emerchantpay.frontend.cart.validate',
        options:  ['seo'=> 'false'],
        defaults: ['auth_required' => false, 'csrf_protected' => false, '_routeScope' => ['storefront']],
        methods:  ["POST"]
    )]
    public function validateCart(SalesChannelContext $context): JsonResponse
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);

        if ($cart->getLineItems()->count() === 0) {
            return new JsonResponse(['valid' => false]);
        }

        return new JsonResponse(['valid' => true]);
    }
}
