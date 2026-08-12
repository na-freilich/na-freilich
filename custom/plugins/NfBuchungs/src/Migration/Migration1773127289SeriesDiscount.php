<?php declare(strict_types=1);

namespace Nf\Booking\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1773127289SeriesDiscount extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1773127289;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `nf_booking_series_discount` (
                `id` BINARY(16) NOT NULL,
                `min_count` INT(11) NOT NULL,
                `discount_percentage` DOUBLE NOT NULL,
                `active` TINYINT(1) DEFAULT '1',
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.min_count` (`min_count`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
    }
}
