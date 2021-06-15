<?php declare(strict_types=1);

namespace Emerchantpay\Genesis\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1623156998ShopwarePaymentId extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1623156998;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
ALTER TABLE emerchantpay_genesis_payment_entity ADD shopware_payment_id varchar(255);
SQL;
        $connection->executeUpdate($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
