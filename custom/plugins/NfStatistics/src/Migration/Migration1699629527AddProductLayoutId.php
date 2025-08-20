<?php declare(strict_types=1);

namespace Nf\Statistics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1699629527AddProductLayoutId extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1699629527;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `nf_stat_product_layout_views` (
`id`  binary(16) NOT NULL ,
`user_session`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ,
`product_layout_id`  binary(16) NULL,
`order_quantity`  int UNSIGNED NOT NULL DEFAULT 0 ,
`view_date`  date NOT NULL ,
`views`  int UNSIGNED NOT NULL DEFAULT 0 ,
`created_at`  datetime NOT NULL ,
`updated_at`  datetime NULL DEFAULT NULL ,
PRIMARY KEY (`id`),
CONSTRAINT `fk.nf_stat_product_layout_views.product_layout_id` FOREIGN KEY (`product_layout_id`) REFERENCES `cms_page` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
UNIQUE INDEX `user_session.product_layout_id.view_date` (`user_session`, `product_layout_id`, `view_date`) USING BTREE ,
INDEX `order_quantity` (`order_quantity`) USING BTREE ,
INDEX `views` (`views`) USING BTREE ,
INDEX `fk.nf_stat_product_layout_views.product_layout_id` (`product_layout_id`) USING BTREE 
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci
ROW_FORMAT=Dynamic
;
ALTER TABLE `nf_stat_product_views` MODIFY COLUMN `views`  int UNSIGNED NOT NULL DEFAULT 0 AFTER `view_date`;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
