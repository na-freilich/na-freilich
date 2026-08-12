<?php declare(strict_types=1);

namespace Nf\Booking\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1770284103BookingPricing extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1770284103;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `nf_booking_season` (
                `id` BINARY(16) NOT NULL,
                `start_date` VARCHAR(4) NOT NULL,
                `end_date` VARCHAR(4) NOT NULL,
                `active` TINYINT(1) DEFAULT 1,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `nf_booking_season_translation` (
                `nf_booking_season_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`nf_booking_season_id`, `language_id`),
                CONSTRAINT `fk.nf_booking_season_translation.nf_booking_season_id` 
                    FOREIGN KEY (`nf_booking_season_id`) 
                    REFERENCES `nf_booking_season` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.nf_booking_season_translation.language_id` 
                    FOREIGN KEY (`language_id`) 
                    REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `nf_booking_price_rule` (
                `id` BINARY(16) NOT NULL,
                `season_id` BINARY(16) NOT NULL,
                `days` JSON NOT NULL,
                `start_time` TIME NOT NULL DEFAULT "00:00:00",
                `end_time` TIME NOT NULL DEFAULT "23:59:59",
                `price` DECIMAL(10, 2) NOT NULL,
                `price_subsequent` DECIMAL(10, 2) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.nf_booking_price_rule.season_id` 
                    FOREIGN KEY (`season_id`) 
                    REFERENCES `nf_booking_season` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }
}
