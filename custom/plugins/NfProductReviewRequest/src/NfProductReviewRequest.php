<?php declare(strict_types=1);

namespace Nf\ProductReviewRequest;

use Nf\ProductReviewRequest\Checkout\ProductReviewMailTemplate;
use Nf\ProductReviewRequest\Core\Content\ProductReviewRequest\ProductReviewRequestDefinition;
use Nf\ProductReviewRequest\Core\Flow\ProductReviewDueEvent;
use Nf\ProductReviewRequest\ScheduledTask\ProductReviewRequestTask;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Kernel;

class NfProductReviewRequest extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        $connection = Kernel::getConnection();

        $connection->executeStatement(
            'DROP TABLE IF EXISTS `' . ProductReviewRequestDefinition::ENTITY_NAME . '`',
        );
        $connection->executeStatement(
            'DELETE FROM scheduled_task WHERE name = :name',
            ['name' => ProductReviewRequestTask::getTaskName()],
        );
        $connection->executeStatement(
            'DELETE FROM flow WHERE id = :id',
            ['id' => Uuid::fromHexToBytes(ProductReviewMailTemplate::FLOW_ID)],
        );

        $connection->executeStatement(
            'DELETE mail_template_translation FROM mail_template_translation
             INNER JOIN mail_template ON mail_template.id = mail_template_translation.mail_template_id
             INNER JOIN mail_template_type ON mail_template_type.id = mail_template.mail_template_type_id
             WHERE mail_template_type.technical_name = :name',
            ['name' => ProductReviewMailTemplate::TECHNICAL_NAME],
        );
        $connection->executeStatement(
            'DELETE mail_template FROM mail_template
             INNER JOIN mail_template_type ON mail_template_type.id = mail_template.mail_template_type_id
             WHERE mail_template_type.technical_name = :name',
            ['name' => ProductReviewMailTemplate::TECHNICAL_NAME],
        );
        $connection->executeStatement(
            'DELETE mail_template_type_translation FROM mail_template_type_translation
             INNER JOIN mail_template_type ON mail_template_type.id = mail_template_type_translation.mail_template_type_id
             WHERE mail_template_type.technical_name = :name',
            ['name' => ProductReviewMailTemplate::TECHNICAL_NAME],
        );
        $connection->executeStatement(
            'DELETE FROM mail_template_type WHERE technical_name = :name',
            ['name' => ProductReviewMailTemplate::TECHNICAL_NAME],
        );
    }

    /**
     * @return array<class-string>
     */
    protected function getActionEventClasses(): array
    {
        return [
            ProductReviewDueEvent::class,
        ];
    }
}
