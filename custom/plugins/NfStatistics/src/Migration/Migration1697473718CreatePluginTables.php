<?php declare(strict_types=1);

namespace Nf\Statistics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1697473718CreatePluginTables extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1697473718;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `nf_stat_product_views` (
  `id` binary(16) NOT NULL,
  `product_id` binary(16) NOT NULL,
  `view_date` date NOT NULL,
  `views` int DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product.view_date` (`product_id`,`view_date`),
  KEY `fk.nf_stat_product_views.product_id` (`product_id`),
  CONSTRAINT `fk.nf_stat_product_views.product_id` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
