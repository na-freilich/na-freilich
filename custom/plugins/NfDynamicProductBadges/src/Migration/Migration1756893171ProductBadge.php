<?php declare(strict_types=1);

namespace Nf\DynamicProductBadges\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1756893171ProductBadge extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1756893171;
    }

    public function update(Connection $connection): void
    {
        $sql = <<< 'SQL'
            CREATE TABLE IF NOT EXISTS `nf_custom_badge` (
                `id` BINARY(16) NOT NULL,
                `name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                `text` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                `color` VARCHAR(7) COLLATE utf8mb4_unicode_ci,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;

        $connection->executeStatement($sql);
    }
}
