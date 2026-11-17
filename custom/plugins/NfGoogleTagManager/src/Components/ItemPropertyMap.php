<?php declare(strict_types=1);

namespace NfGoogleTagManager\Components;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class ItemPropertyMap
{
    public const SLOTS = 4;

    /**
     * @var list<string>
     */
    private const RESERVED = [
        'item_id',
        'item_name',
        'affiliation',
        'item_brand',
        'item_category',
        'item_variant',
        'item_list_id',
        'item_list_name',
        'price',
        'quantity',
        'discount',
        'index',
        'currency',
    ];

    public function __construct(
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    /**
     * @return array<string, string> property group id => dataLayer item field
     */
    public function forSalesChannel(string $salesChannelId): array
    {
        $map = [];
        for ($slot = 1; $slot <= self::SLOTS; ++$slot) {
            $groupId = trim($this->systemConfigService->getString(
                'NfGoogleTagManager.config.itemPropertyGroup' . $slot,
                $salesChannelId
            ));
            $parameter = $this->normalizeParameter($this->systemConfigService->getString(
                'NfGoogleTagManager.config.itemPropertyParameter' . $slot,
                $salesChannelId
            ));
            if ($groupId === '' || $parameter === null) {
                continue;
            }

            $map[$groupId] = $parameter;
        }

        return $map;
    }

    public function normalizeParameter(string $name): ?string
    {
        $normalized = strtolower(trim($name));
        $normalized = preg_replace('/[^a-z0-9_]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');
        if ($normalized === '' || preg_match('/^[a-z][a-z0-9_]{0,39}$/', $normalized) !== 1) {
            return null;
        }
        if (in_array($normalized, self::RESERVED, true)) {
            return null;
        }

        return $normalized;
    }
}
