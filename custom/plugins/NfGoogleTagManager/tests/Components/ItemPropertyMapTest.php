<?php declare(strict_types=1);

namespace NfGoogleTagManager\Tests\Components;

use NfGoogleTagManager\Components\ItemPropertyMap;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ItemPropertyMapTest extends TestCase
{
    public function testMapsAGroupToTheFieldNameConfiguredForTheShop(): void
    {
        $config = $this->createMock(SystemConfigService::class);
        $config->method('getString')->willReturnCallback(
            static function (string $key): string {
                return match ($key) {
                    'NfGoogleTagManager.config.itemPropertyGroup1' => 'group-size',
                    'NfGoogleTagManager.config.itemPropertyParameter1' => 'Size',
                    'NfGoogleTagManager.config.itemPropertyGroup2' => 'group-ignored',
                    'NfGoogleTagManager.config.itemPropertyParameter2' => 'item_id',
                    default => '',
                };
            }
        );

        $map = (new ItemPropertyMap($config))->forSalesChannel('sales-channel');

        $this->assertSame(['group-size' => 'size'], $map);
    }

    public function testRejectsEmptyAndReservedParameterNames(): void
    {
        $map = new ItemPropertyMap($this->createMock(SystemConfigService::class));

        $this->assertNull($map->normalizeParameter(''));
        $this->assertNull($map->normalizeParameter('item_name'));
        $this->assertSame('item_category2', $map->normalizeParameter('item_category2'));
        $this->assertSame('item_category_2', $map->normalizeParameter('Item Category 2'));
    }
}
