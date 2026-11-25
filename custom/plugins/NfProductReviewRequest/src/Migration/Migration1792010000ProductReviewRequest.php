<?php declare(strict_types=1);

namespace Nf\ProductReviewRequest\Migration;

use Doctrine\DBAL\Connection;
use Nf\ProductReviewRequest\Checkout\ProductReviewMailTemplate;
use Nf\ProductReviewRequest\Core\Flow\ProductReviewDueEvent;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1792010000ProductReviewRequest extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1792010000;
    }

    public function update(Connection $connection): void
    {
        $this->createTable($connection);

        $createdAt = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $typeId = Uuid::fromHexToBytes(ProductReviewMailTemplate::TYPE_ID);
        $templateId = Uuid::fromHexToBytes(ProductReviewMailTemplate::TEMPLATE_ID);

        $typeExists = (bool) $connection->fetchOne(
            'SELECT 1 FROM mail_template_type WHERE technical_name = :name',
            ['name' => ProductReviewMailTemplate::TECHNICAL_NAME],
        );
        $templateExists = (bool) $connection->fetchOne(
            'SELECT 1 FROM mail_template WHERE id = :id',
            ['id' => $templateId],
        );

        if (!$typeExists) {
            $connection->insert('mail_template_type', [
                'id' => $typeId,
                'technical_name' => ProductReviewMailTemplate::TECHNICAL_NAME,
                'available_entities' => json_encode([
                    'order' => 'order',
                    'salesChannel' => 'sales_channel',
                ], \JSON_THROW_ON_ERROR),
                'created_at' => $createdAt,
            ]);
        }

        if (!$templateExists) {
            $template = [
                'id' => $templateId,
                'mail_template_type_id' => $typeId,
                'system_default' => 0,
                'created_at' => $createdAt,
            ];

            if ($this->columnExists($connection, 'mail_template', 'was_modified_by_user')) {
                $template['was_modified_by_user'] = 0;
            }

            $connection->insert('mail_template', $template);
        }

        if (!$typeExists || !$templateExists) {
            /** @var list<array{id: string, code: string}> $languages */
            $languages = $connection->fetchAllAssociative(
                'SELECT language.id AS id, locale.code AS code
                 FROM language
                 INNER JOIN locale ON locale.id = language.locale_id',
            );

            foreach ($languages as $language) {
                $copy = str_starts_with((string) $language['code'], 'de') ? $this->german() : $this->english();

                if (!$typeExists) {
                    $connection->insert('mail_template_type_translation', [
                        'mail_template_type_id' => $typeId,
                        'language_id' => $language['id'],
                        'name' => $copy['typeName'],
                        'created_at' => $createdAt,
                    ]);
                }

                if (!$templateExists) {
                    $connection->insert('mail_template_translation', [
                        'mail_template_id' => $templateId,
                        'language_id' => $language['id'],
                        'sender_name' => '{{ salesChannel.name }}',
                        'subject' => $copy['subject'],
                        'description' => $copy['description'],
                        'content_html' => $copy['html'],
                        'content_plain' => $copy['plain'],
                        'created_at' => $createdAt,
                    ]);
                }
            }
        }

        $this->createFlow($connection, $createdAt);
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function createTable(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `nf_product_review_request` (
                `id` BINARY(16) NOT NULL,
                `order_id` BINARY(16) NOT NULL,
                `order_version_id` BINARY(16) NOT NULL,
                `send_at` DATETIME(3) NULL,
                `triggered_at` DATETIME(3) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.nf_product_review_request.order` (`order_id`, `order_version_id`),
                KEY `idx.nf_product_review_request.send_at` (`send_at`, `triggered_at`),
                CONSTRAINT `fk.nf_product_review_request.order_id`
                    FOREIGN KEY (`order_id`, `order_version_id`)
                    REFERENCES `order` (`id`, `version_id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    private function createFlow(Connection $connection, string $createdAt): void
    {
        $flowId = Uuid::fromHexToBytes(ProductReviewMailTemplate::FLOW_ID);
        $exists = (bool) $connection->fetchOne(
            'SELECT 1 FROM flow WHERE id = :id',
            ['id' => $flowId],
        );

        if ($exists) {
            return;
        }

        $connection->insert('flow', [
            'id' => $flowId,
            'name' => 'Produktbewertung einfordern',
            'description' => 'Sendet die Produktbewertungsmail, nachdem die Lieferung den Status Versendet erreicht hat und die konfigurierte Wartezeit abgelaufen ist.',
            'event_name' => ProductReviewDueEvent::EVENT_NAME,
            'priority' => 1,
            'active' => 1,
            'invalid' => 0,
            'created_at' => $createdAt,
        ]);

        $connection->insert('flow_sequence', [
            'id' => Uuid::fromHexToBytes(ProductReviewMailTemplate::SEQUENCE_ID),
            'flow_id' => $flowId,
            'action_name' => 'action.mail.send',
            'config' => json_encode([
                'mailTemplateId' => ProductReviewMailTemplate::TEMPLATE_ID,
                'mailTemplateTypeId' => ProductReviewMailTemplate::TYPE_ID,
                'recipient' => [
                    'data' => [],
                    'type' => 'default',
                ],
            ], \JSON_THROW_ON_ERROR),
            'position' => 1,
            'display_group' => 1,
            'true_case' => 0,
            'created_at' => $createdAt,
        ]);
    }

    /**
     * @return array{typeName: string, subject: string, description: string, html: string, plain: string}
     */
    private function german(): array
    {
        return [
            'typeName' => 'Produktbewertung einfordern',
            'subject' => 'Wie hat Ihnen Ihre Bestellung {{ order.orderNumber }} gefallen?',
            'description' => 'Bitte um eine Produktbewertung',
            'html' => <<<'HTML'
<div style="font-family: Arial, sans-serif; font-size: 16px; color: #333333;">
    <p>Hallo {{ order.orderCustomer.firstName }} {{ order.orderCustomer.lastName }},</p>
    <p>vielen Dank für Ihre Bestellung <strong>{{ order.orderNumber }}</strong>.</p>
    <p>Wir freuen uns über Ihre Bewertung:</p>
    <ul>
        {% for item in productReviewLinks|default([]) %}
            <li><a href="{{ item.url }}">{{ item.label }} bewerten</a></li>
        {% endfor %}
    </ul>
</div>
HTML,
            'plain' => <<<'TEXT'
Hallo {{ order.orderCustomer.firstName }} {{ order.orderCustomer.lastName }},

vielen Dank für Ihre Bestellung {{ order.orderNumber }}.

Wir freuen uns über Ihre Bewertung:
{% for item in productReviewLinks|default([]) %}
- {{ item.label }}: {{ item.url }}
{% endfor %}
TEXT,
        ];
    }

    /**
     * @return array{typeName: string, subject: string, description: string, html: string, plain: string}
     */
    private function english(): array
    {
        return [
            'typeName' => 'Request a product review',
            'subject' => 'How was your order {{ order.orderNumber }}?',
            'description' => 'Asks the customer to review the products.',
            'html' => <<<'HTML'
<div style="font-family: Arial, sans-serif; font-size: 16px; color: #333333;">
    <p>Hello {{ order.orderCustomer.firstName }} {{ order.orderCustomer.lastName }},</p>
    <p>thank you for your order <strong>{{ order.orderNumber }}</strong>.</p>
    <p>We would appreciate your review:</p>
    <ul>
        {% for item in productReviewLinks|default([]) %}
            <li><a href="{{ item.url }}">Review {{ item.label }}</a></li>
        {% endfor %}
    </ul>
</div>
HTML,
            'plain' => <<<'TEXT'
Hello {{ order.orderCustomer.firstName }} {{ order.orderCustomer.lastName }},

thank you for your order {{ order.orderNumber }}.

We would appreciate your review:
{% for item in productReviewLinks|default([]) %}
- {{ item.label }}: {{ item.url }}
{% endfor %}
TEXT,
        ];
    }
}
