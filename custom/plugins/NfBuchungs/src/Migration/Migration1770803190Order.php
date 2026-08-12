<?php declare(strict_types=1);

namespace Nf\Booking\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1770803190Order extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1770803190;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement("
    CREATE TABLE IF NOT EXISTS `nf_booking` (
        `id` BINARY(16) NOT NULL,
        `product_id` BINARY(16) NULL,
        `customer_id` BINARY(16) NULL,
        `cart_token` VARCHAR(255) NULL,
        `expires_at` DATETIME(3) NULL,
        `order_number` VARCHAR(64) NOT NULL,
        `order_id` BINARY(16) NULL,
        `total_price` DOUBLE NOT NULL,
        `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
        `created_at` DATETIME(3) NOT NULL,
        `updated_at` DATETIME(3) NULL,
        PRIMARY KEY (`id`),
        CONSTRAINT `fk.nf_booking.customer_id` FOREIGN KEY (`customer_id`)
            REFERENCES `customer` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

        $connection->executeStatement("
    CREATE TABLE IF NOT EXISTS `nf_booking_item` (
        `id` BINARY(16) NOT NULL,
        `booking_id` BINARY(16) NOT NULL,
        `location_id` BINARY(16) NOT NULL,
        `booking_date` DATE NOT NULL,
        `start_time` TIME NOT NULL,
        `end_time` TIME NOT NULL,
        `unit_price` DOUBLE NOT NULL,
        `created_at` DATETIME(3) NOT NULL,
        `updated_at` DATETIME(3) NULL,
        PRIMARY KEY (`id`),
        CONSTRAINT `fk.nf_booking_item.booking_id` FOREIGN KEY (`booking_id`)
            REFERENCES `nf_booking` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk.nf_booking_item.location_id` FOREIGN KEY (`location_id`)
            REFERENCES `nf_booking_location` (`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
    }
}
