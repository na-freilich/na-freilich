<?php declare(strict_types=1);

namespace Nf\Booking\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1770792941Location extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1770792941;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement("
    CREATE TABLE IF NOT EXISTS `nf_booking_location` (
        `id` BINARY(16) NOT NULL,
        `active` TINYINT(1) DEFAULT '1',
        `created_at` DATETIME(3) NOT NULL,
        `updated_at` DATETIME(3) NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");


        $connection->executeStatement("
    CREATE TABLE IF NOT EXISTS `nf_booking_location_translation` (
        `nf_booking_location_id` BINARY(16) NOT NULL,
        `language_id` BINARY(16) NOT NULL,
        `name` VARCHAR(255) NOT NULL,
        `created_at` DATETIME(3) NOT NULL,
        `updated_at` DATETIME(3) NULL,
        PRIMARY KEY (`nf_booking_location_id`, `language_id`),
        CONSTRAINT `fk.location_translation.location_id` FOREIGN KEY (`nf_booking_location_id`) 
            REFERENCES `nf_booking_location` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk.location_translation.language_id` FOREIGN KEY (`language_id`) 
            REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
    }
}
