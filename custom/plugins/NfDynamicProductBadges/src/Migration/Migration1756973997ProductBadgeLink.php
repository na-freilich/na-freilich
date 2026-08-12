<?php declare(strict_types=1);

namespace Nf\DynamicProductBadges\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1756973997ProductBadgeLink extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1756973997;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `nf_custom_badge_product` (
                `id` BINARY(16) NOT NULL,
                `custom_badge_id` BINARY(16) NOT NULL,
                `product_id` BINARY(16) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,

                PRIMARY KEY (`id`),
                CONSTRAINT `fk.cpbp.custom_badge_id` 
                    FOREIGN KEY (`custom_badge_id`) 
                    REFERENCES `nf_custom_badge` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.cpbp.product_id` 
                    FOREIGN KEY (`product_id`) 
                    REFERENCES `product` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }
}
