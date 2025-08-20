<?php declare(strict_types=1);

namespace Nf\Statistics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1735807916currentUsers extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1735807916;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `nf_stat_current_users` (
  `id` binary(16) NOT NULL,
  `remote_addr` varchar(255) NOT NULL,
  `user_id` binary(16) DEFAULT NULL,
  `token` varchar(50) DEFAULT NULL,
  `device_type` varchar(50) DEFAULT NULL,  
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $connection->executeStatement($sql);
    }
}
