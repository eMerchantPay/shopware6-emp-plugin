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

namespace Emerchantpay\Genesis\Api\Utils\Administration;

use Genesis\Utils\Currency;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Attribute\Route;

if (file_exists(dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/autoload.php')) {
    $loader = require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/autoload.php';
    if ($loader !== true) {
        spl_autoload_unregister([$loader, 'loadClass']);
        $loader->register(false);
    }
}

class EmerchantpayGenesisUtilsController extends AbstractController
{
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @OA\Get(
     *     path="/emerchantpay-v1/genesis/utils/convert-amount-exponent/{amount}/{currency}",
     *     description="Converts the minor amount to exponent",
     *     operationId="convertAmountToExponent",
     *     tags={"Admin API", "Emerchantpay"},
     *     @OA\Parameter(
     *         parameter="amount",
     *         name="amount",
     *         in="path",
     *         description="Amount",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         parameter="currency",
     *         name="currency",
     *         in="path",
     *         description="Currency",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Converted amount",
     *         @OA\JsonContent(type="array")
     *     )
     * )
     */
    #[Route(
        path:     '/api/v{version}/emerchantpay-v1/genesis/utils/convert-amount-exponent/{amount}/{currency}',
        name:     'api.emerchantpay.genesis.version-endpoint.utils.convert-currency-exponent',
        defaults: ['_routeScope' => ['api']],
        methods:  ['GET']
    )]
    #[Route(
        path:     '/api/emerchantpay-v1/genesis/utils/convert-amount-exponent/{amount}/{currency}',
        name:     'api.emerchantpay.genesis.utils.convert-currency-exponent',
        defaults: ['_routeScope' => ['api']],
        methods:  ['GET']
    )]
    public function convertAmountToExponent(int $amount, string $currency, Context $context): JsonResponse
    {
        return new JsonResponse(
            ['amount' => Currency::exponentToAmount($amount, $currency)]
        );
    }
}
