<?php declare(strict_types=1);

namespace Nf\Booking\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1773139920AddDiscountFields extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1773139920;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `nf_booking` 
            ADD COLUMN `original_price` DECIMAL(10, 2) NULL AFTER `total_price`,
            ADD COLUMN `discount_amount` DECIMAL(10, 2) NULL AFTER `original_price`,
            ADD COLUMN `discount_id` BINARY(16) NULL AFTER `discount_amount`;
        ');

        $connection->executeStatement('
            ALTER TABLE `nf_booking`
            ADD CONSTRAINT `fk.nf_booking.discount_id` 
            FOREIGN KEY (`discount_id`) REFERENCES `nf_booking_series_discount` (`id`) 
            ON DELETE SET NULL ON UPDATE CASCADE;
        ');
    }
}
