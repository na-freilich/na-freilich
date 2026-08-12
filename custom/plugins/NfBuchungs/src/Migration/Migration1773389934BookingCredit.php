<?php declare(strict_types=1);

namespace Nf\Booking\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1773389934BookingCredit extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1773389934;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `nf_booking_credit` (
                `id` BINARY(16) NOT NULL,
                `total_slots` DOUBLE NOT NULL DEFAULT 0,
                `used_slots` DOUBLE NOT NULL DEFAULT 0,
                `history` JSON NULL,
                `active` TINYINT(1) COLLATE utf8mb4_unicode_ci DEFAULT 0,
                `customer_id` BINARY(16) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.nf_booking_credit.customer_id` FOREIGN KEY (`customer_id`) 
                    REFERENCES `customer` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $this->addColumnIfNotExists($connection, 'nf_booking', 'total_credit_slots', 'DOUBLE DEFAULT 0 AFTER `customer_id`');
        $this->addColumnIfNotExists($connection, 'nf_booking', 'total_credit_amount', 'DOUBLE DEFAULT 0 AFTER `total_credit_slots`');
        $this->addColumnIfNotExists($connection, 'nf_booking', 'credit_info', 'JSON NULL AFTER `total_credit_amount`');
    }

    private function addColumnIfNotExists(Connection $connection, string $table, string $column, string $type): void
    {
        $exists = $connection->fetchOne("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if (!$exists) {
            $connection->executeStatement("ALTER TABLE `$table` ADD `$column` $type");
        }
    }
}
