<?php declare(strict_types=1);

namespace Nf\CustomFinishPage\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1779358155ReferralOrderCodes extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1779358155;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `nf_promotion_order_code` (
                `id` BINARY(16) NOT NULL,
                `customer_id` BINARY(16) NULL,
                `order_id` BINARY(16) NULL,
                `friend_code` VARCHAR(255) NOT NULL,
                `owner_code` VARCHAR(255) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `fk.referral_order_codes.customer_id` (`customer_id`),
                KEY `fk.referral_order_codes.order_id` (`order_id`),
                CONSTRAINT `fk.referral_order_codes.customer_id` FOREIGN KEY (`customer_id`) 
                    REFERENCES `customer` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk.referral_order_codes.order_id` FOREIGN KEY (`order_id`) 
                    REFERENCES `order` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
}
