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

use Emerchantpay\Genesis\Service\Flow\ReturnUrl;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class GenesisReturnController extends StorefrontController
{
    /**
     * @var ReturnUrl
     */
    private $returnUrlService;

    public function __construct(ReturnUrl $return_url)
    {
        $this->returnUrlService = $return_url;
    }

    /**
     * @Route(
     *     "/emerchantpay/return/{token}",
     *     name="frontend.emerchantpay.return",
     *     options={"seo"="false"},
     *     methods={"GET"},
     *     defaults={"auth_required"=false, "csrf_protected"=false}
     * )
     */
    public function redirectOrderEndpoint(string $token): RedirectResponse
    {
        return $this->redirect($this->returnUrlService->getOrderEndpoint($token));
    }
}
