<?php declare(strict_types=1);

namespace Nf\Statistics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1735808837Visitors extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1735808837;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS `nf_stat_visitors` (
  `id` binary(16) NOT NULL,
  `sales_channel_id` binary(16) NOT NULL,
  `date` date NOT NULL,
  `page_impressions` int(11) DEFAULT NULL,
  `unique_visits` int(11) DEFAULT NULL,
  `device_type` varchar(50) DEFAULT NULL,  
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  CONSTRAINT `fk.nf_stat_visitors.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE INDEX `ui.nf_stat_product_layout_views.view_date` (`sales_channel_id`, `date`, `device_type`) USING BTREE ,

  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $connection->executeStatement($sql);
    }
}
