<?php declare(strict_types=1);

namespace Nf\CustomFinishPage\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
class Migration1779275747CreatePromotions extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1779275747;
    }

    public function update(Connection $connection): void
    {
        $this->createPromotion(
            $connection,
            'Danke Modul Freund',
            'FRIEND-%s%s%d%s%s',
            'friend_promo',
            5.0,
            'absolute'
        );

        $this->createPromotion(
            $connection,
            'Danke Modul Kunde',
            'SAVE-%s%s%d%s%s',
            'owner_promo',
            10.0,
            'absolute'
        );
    }

    private function createPromotion(
        Connection $connection,
        string $name,
        string $pattern,
        string $referralType,
        float $value,
        string $type): void
    {
        $promotionId = Uuid::randomBytes();

        $connection->insert('promotion', [
            'id' => $promotionId,
            'active' => 1,
            'use_codes' => 1,
            'use_individual_codes' => 1,
            'max_redemptions_global'=> 1,
            'max_redemptions_per_customer'=> 1,
            'exclusive' => 0,
            'priority' => 1,
            'individual_code_pattern' => $pattern,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $connection->insert('promotion_translation', [
            'promotion_id' => $promotionId,
            'language_id' => $this->getLanguageIdByCode($connection, 'de-DE'),
            'name' => $name,
            'custom_fields' => json_encode(['referral_type' => $referralType]),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $connection->insert('promotion_discount', [
            'id' => Uuid::randomBytes(),
            'promotion_id' => $promotionId,
            'scope' => 'cart',
            'type' => $type,
            'value' => $value,
            'consider_advanced_rules' => 0,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $salesChannelIds = $connection->fetchAllAssociative(
            'SELECT id FROM sales_channel WHERE type_id = :type',
            ['type' => Uuid::fromHexToBytes(Defaults::SALES_CHANNEL_TYPE_STOREFRONT)]
        );

        foreach ($salesChannelIds as $sc) {
            $connection->insert('promotion_sales_channel', [
                'id' => Uuid::randomBytes(),
                'promotion_id' => $promotionId,
                'sales_channel_id' => $sc['id'],
                'priority' => 1,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
            ]);
        }
    }

    private function getLanguageIdByCode(Connection $connection, string $code): string
    {
        return $connection->fetchOne(
            'SELECT `language`.id FROM `language` 
             INNER JOIN `locale` ON `language`.locale_id = `locale`.id 
             WHERE `locale`.code = ?',
            [$code]
        );
    }
}
