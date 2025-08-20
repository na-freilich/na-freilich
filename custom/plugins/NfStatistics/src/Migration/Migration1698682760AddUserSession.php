<?php declare(strict_types=1);

namespace Nf\Statistics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1698682760AddUserSession extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1698682760;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `nf_stat_product_views`
ADD `user_session` varchar(255) NOT NULL AFTER `id`;
ALTER TABLE `nf_stat_product_views`
ADD UNIQUE `product_id.view_date.user_session` (`product_id`, `view_date`, `user_session`),
DROP INDEX `product.view_date`;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
