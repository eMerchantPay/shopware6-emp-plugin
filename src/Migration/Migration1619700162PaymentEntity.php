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

namespace Emerchantpay\Genesis\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1619700162PaymentEntity extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1619700162;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
CREATE TABLE IF NOT EXISTS `emerchantpay_genesis_payment_entity` (
    `id`                BINARY(16)      NOT NULL,
    `transaction_id`    VARCHAR(255)    NOT NULL,
    `unique_id`         VARCHAR(255),
    `reference_id`      VARCHAR(255),
    `payment_method`    VARCHAR(64)     NOT NULL,
    `terminal_token`    VARCHAR(255),
    `status`            VARCHAR(32)     NOT NULL,
    `order_id`          VARCHAR(255),
    `transaction_type`  VARCHAR(128)    NOT NULL,
    `amount`            INTEGER         NOT NULL,
    `currency`          VARCHAR(6)      NOT NULL,
    `mode`              VARCHAR(6)      NOT NULL,
    `message`           LONGTEXT,
    `technical_message` LONGTEXT,
    `request`           LONGTEXT,
    `response`          LONGTEXT,
    `created_at`        DATETIME,
    `updated_at`        DATETIME,
    PRIMARY KEY (id),
    UNIQUE INDEX idx_unique_id (unique_id),
    UNIQUE INDEX idx_unique_transaction_type (transaction_id, transaction_type)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
SQL;

        $connection->executeUpdate($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Do not remove the transaction data
    }
}
