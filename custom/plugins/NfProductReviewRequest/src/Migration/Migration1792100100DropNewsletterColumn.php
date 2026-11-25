<?php declare(strict_types=1);

namespace Nf\ProductReviewRequest\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1792100100DropNewsletterColumn extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1792100100;
    }

    public function update(Connection $connection): void
    {
        if (!$this->columnExists($connection, 'nf_product_review_request', 'newsletter_subscribed')) {
            return;
        }

        $connection->executeStatement(
            'ALTER TABLE `nf_product_review_request` DROP COLUMN `newsletter_subscribed`',
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
