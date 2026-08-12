<?php declare(strict_types=1);

namespace Nf\Booking\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1779778446AddCommentToBookingCredit extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1779778446;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
    ALTER TABLE `nf_booking_credit` 
    ADD COLUMN `comment` LONGTEXT NULL AFTER `customer_id`;
');
    }
}
