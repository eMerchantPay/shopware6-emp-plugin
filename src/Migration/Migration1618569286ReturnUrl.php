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

namespace Emerchantpay\Genesis\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1618569286ReturnUrl extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1618569286;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
CREATE TABLE IF NOT EXISTS `emerchantpay_genesis_return_url_mapping` (
    `id`                BINARY(16)      NOT NULL,
    `genesis_token`     VARCHAR(512)    NOT NULL,
    `endpoint`          VARCHAR(2048)   NOT NULL,
    `created_at`        DATETIME,
    `updated_at`        DATETIME,
    PRIMARY KEY (id),
    UNIQUE INDEX idx_unique_genesis_token (genesis_token)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
SQL;

        $connection->executeUpdate($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        $query = <<<SQL
DROP TABLE `emerchantpay_genesis_return_url_mapping`;
SQL;

        $connection->executeQuery($query);
    }
}
