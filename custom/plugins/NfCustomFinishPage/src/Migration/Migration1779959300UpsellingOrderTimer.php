<?php declare(strict_types=1);

namespace Nf\CustomFinishPage\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1779959300UpsellingOrderTimer extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1779959300;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `nf_upselling_order_timer` (
                        `id` BINARY(16) NOT NULL,
                        `order_id` BINARY(16) NOT NULL,
                        `customer_id` BINARY(16) NULL,
                        `discount_amount` DOUBLE NOT NULL,
                        `active` TINYINT(1) NOT NULL DEFAULT 1,
                        `ended_at` DATETIME(3) NOT NULL,
                        `created_at` DATETIME(3) NOT NULL,
                        `updated_at` DATETIME(3) NULL,
                        PRIMARY KEY (`id`),
                        CONSTRAINT `fk.nf_upsell.order_id` FOREIGN KEY (`order_id`)
                            REFERENCES `order` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                        CONSTRAINT `fk.nf_upsell.customer_id` FOREIGN KEY (`customer_id`)
                            REFERENCES `customer` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `nf_upselling_order_timer_product` (
                `upselling_order_timer_id` BINARY(16) NOT NULL,
                `product_id` BINARY(16) NOT NULL,
                `product_version_id` BINARY(16) NOT NULL,
                PRIMARY KEY (`upselling_order_timer_id`, `product_id`, `product_version_id`),
                CONSTRAINT `fk.nf_upsell_p.timer_id` FOREIGN KEY (`upselling_order_timer_id`)
                    REFERENCES `nf_upselling_order_timer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.nf_upsell_p.product_id` FOREIGN KEY (`product_id`, `product_version_id`)
                    REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
}
