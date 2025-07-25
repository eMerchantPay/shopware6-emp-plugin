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
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace Emerchantpay\Genesis\Service\Flow;

use Emerchantpay\Genesis\Constants\Config as ConfigKeys;
use Emerchantpay\Genesis\Core\Flow\ReturnUrl\ReturnUrlEntity;
use Emerchantpay\Genesis\Utils\Config;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ReturnUrl
{
    /**
     * @var EntityRepository
     */
    private EntityRepository $returnUrlRepository;

    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    /**
     * @var Config
     */
    private Config $config;

    public function __construct(EntityRepository $returnUrlRepository, RouterInterface $router, Config $config)
    {
        $this->returnUrlRepository = $returnUrlRepository;
        $this->router = $router;
        $this->config = $config;
    }

    public function generateReturnUrl(string $return_url): string
    {
        $token = $this->generateToken($return_url);

        $this->returnUrlRepository->create([
            [
                'id' => Uuid::randomHex(),
                'genesis_token' => $token,
                'endpoint' => $return_url,
            ],
        ], Context::createDefaultContext());

        return $this->router->generate(
            $this->isIframeEnabled() ? 'frontend.emerchantpay.return_iframe' : 'frontend.emerchantpay.return',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function getOrderEndpoint(string $token): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('genesis_token', $token));

        /** @var ReturnUrlEntity $result */
        $result = $this->returnUrlRepository->search($criteria, Context::createDefaultContext())->first();

        if ($result === null) {
            return null;
        }

        return $result->getEndpoint();
    }

    private function generateToken(string $return_url): string
    {
        return $hash = hash('sha512', $return_url);
    }

    /**
     * Check if iframe processing is enabled
     *
     * @return bool
     */
    private function isIframeEnabled(): bool
    {
        return (bool)$this->config->getCheckout()[ConfigKeys::CHECKOUT_IFRAME_PROCESSING];
    }
}
