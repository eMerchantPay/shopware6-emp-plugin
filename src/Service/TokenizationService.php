<?php
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

use Genesis\API\Constants\Transaction\States;
use Genesis\Genesis;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class TransactionHelper
 */
class TokenizationService
{
    /**
     * @var EntityRepository
     */
    private $tokenizationConsumerRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param EntityRepository $tokenizationConsumerRepository
     */
    public function __construct(
        EntityRepository $tokenizationConsumerRepository,
        LoggerInterface $logger
    ) {
        $this->tokenizationConsumerRepository = $tokenizationConsumerRepository;
        $this->logger                         = $logger;
    }

    /**
     * @param string $email
     *
     * @return string|null
     */
    public function fetchConsumerIdFromDb(string $email): ?string
    {
        $context  = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));
        $consumer = $this->tokenizationConsumerRepository->search($criteria, $context)->first();

        return $consumer ? $consumer->getConsumerId() : null;
    }

    /**
     * @param $email
     *
     * @return string|null
     */
    public function retrieveConsumerIdFromApi($email): ?string
    {
        try {
            $genesis = new Genesis('NonFinancial\Consumers\Retrieve');
            $genesis->request()->setEmail($email);
            $genesis->execute();

            $response = $genesis->response()->getResponseObject();

            if (!$this->isConsumerEnabled($response)) {
                throw new \Exception('Consumer is not enabled');
            }

            return $response->consumer_id;
        } catch (\Exception $exception) {
            $this->logger->debug(
                sprintf(
                    "Error retrieving consumer_id from API. Error is: %s",
                    $exception->getMessage()
                )
            );

            return null;
        }
    }

    /**
     * @param string $email
     * @param string $consumerId
     *
     * @return void
     */
    public function saveConsumerId(string $email, string $consumerId): void
    {
        if (!$this->fetchConsumerIdFromDb($email)) {
            $id = Uuid::randomHex();
            $context = Context::createDefaultContext();
            $this->tokenizationConsumerRepository->create([
                [
                    'id'         => $id,
                    'email'      => $email,
                    'consumerId' => $consumerId
                ]
            ], $context);
        }
    }

    /**
     * @param $email
     *
     * @return string|null
     */
    public function getConsumerId($email)
    {
        $consumerId = $this->fetchConsumerIdFromDb($email);

        if (!$consumerId) {
            $consumerId = $this->retrieveConsumerIdFromApi($email);
        }

        return $consumerId;
    }

    /**
     * @param $response
     *
     * @return bool
     */
    private function isConsumerEnabled($response): bool
    {
        $state = new States($response->status);

        return $state->isEnabled();
    }
}
