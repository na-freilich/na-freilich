<?php declare(strict_types=1);

namespace NfGoogleTagManager\Tests\Components;

use NfGoogleTagManager\Components\DataLayerMapper;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\ListPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class DataLayerMapperTest extends TestCase
{
    public function testViewCartUsesGoodsValueAndSkipsAnEmptyCart(): void
    {
        $mapper = new DataLayerMapper();
        $context = $this->context();

        $empty = new Cart('empty');
        $empty->setPrice(CartPrice::createEmpty());
        $this->assertNull($mapper->mapCart($empty, $context));

        $product = new LineItem('line-1', LineItem::PRODUCT_LINE_ITEM_TYPE, 'product-1', 1);
        $product->setLabel('Riesling');
        $product->setPayloadValue('productNumber', 'W-1');
        $product->setPrice($this->price(11.60, 11.60));

        $promotion = new LineItem('promo-1', LineItem::PROMOTION_LINE_ITEM_TYPE);
        $promotion->setPayloadValue('code', 'WEIN10');
        $promotion->setPrice($this->price(-2.0, -2.0));

        $cart = new Cart('cart');
        $cart->add($product);
        $cart->add($promotion);
        $cart->setPrice(new CartPrice(
            9.60,
            28.10,
            11.60,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_GROSS
        ));

        $mapped = $mapper->mapCart($cart, $context);

        $this->assertNotNull($mapped);
        $this->assertSame('view_cart', $mapped['event']);
        $this->assertSame(11.60, $mapped['ecommerce']['value']);
        $this->assertSame('WEIN10', $mapped['ecommerce']['coupon']);
        $this->assertSame(2.0, $mapped['ecommerce']['discount']);
        $this->assertSame('W-1', $mapped['ecommerce']['items'][0]['item_id']);
        $this->assertArrayNotHasKey('shipping', $mapped['ecommerce']);
    }

    public function testProductItemCarriesListPositionVariantAndWineProperties(): void
    {
        $product = $this->wineProduct();
        $product->setCalculatedPrice(new CalculatedPrice(
            10.0,
            10.0,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            1,
            null,
            ListPrice::createFromUnitPrice(10.0, 12.0)
        ));

        $item = (new DataLayerMapper())->mapProductItem($product, $this->context(), 1, [
            'item_list_id' => 'category-1',
            'item_list_name' => 'Rotwein',
            'item_category' => 'Rotwein',
            'index' => 3,
        ], [
            'group-size' => 'size',
        ]);

        $this->assertSame('W-1', $item['item_id']);
        $this->assertSame('Winzer', $item['item_brand']);
        $this->assertSame('Rotwein', $item['item_category']);
        $this->assertSame('category-1', $item['item_list_id']);
        $this->assertSame('Rotwein', $item['item_list_name']);
        $this->assertSame(3, $item['index']);
        $this->assertSame('Jahrgang: 2020 / Flaschengröße: 0,75 l', $item['item_variant']);
        $this->assertSame('0,75 l', $item['size']);
        $this->assertArrayNotHasKey('vintage', $item);
        $this->assertSame(2.0, $item['discount']);
        $this->assertSame('Weinshop', $item['affiliation']);
    }

    public function testItemListUsesTheListingCategoryWhenTheProductHasNone(): void
    {
        $product = new SalesChannelProductEntity();
        $product->setCalculatedPrice($this->price(0.0, 0.0));
        $product->setProductNumber('W-2');
        $product->setTranslated(['name' => 'Offener Wein']);

        $mapped = (new DataLayerMapper())->mapItemList([$product], $this->context(), 'category-9', 'Weisswein', 'Weisswein');

        $this->assertNotNull($mapped);
        $this->assertSame('view_item_list', $mapped['event']);
        $this->assertSame('Weisswein', $mapped['ecommerce']['items'][0]['item_category']);
        $this->assertSame(1, $mapped['ecommerce']['items'][0]['index']);
        $this->assertSame('category-9', $mapped['ecommerce']['item_list_id']);
    }

    public function testPurchaseKeepsAZeroTaxRateAndFillsTheItemFromTheProduct(): void
    {
        $product = $this->wineProduct();
        $product->setId('product-1');

        $lineItem = new OrderLineItemEntity();
        $lineItem->setId('line-1');
        $lineItem->setType(LineItem::PRODUCT_LINE_ITEM_TYPE);
        $lineItem->setReferencedId('product-1');
        $lineItem->setLabel('Riesling');
        $lineItem->setQuantity(1);
        $lineItem->setUnitPrice(11.60);
        $lineItem->setPayload(['productNumber' => 'W-1']);
        $lineItem->setPrice($this->price(11.60, 11.60));

        $order = new OrderEntity();
        $order->setOrderNumber('10001');
        $order->setAmountTotal(11.60);
        $order->setShippingTotal(0.0);
        $order->setPrice(new CartPrice(
            11.60,
            11.60,
            11.60,
            new CalculatedTaxCollection([new CalculatedTax(0.0, 0.0, 11.60)]),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_FREE
        ));
        $order->setLineItems(new OrderLineItemCollection([$lineItem]));

        $mapped = (new DataLayerMapper())->mapPurchase($order, $this->context(), ['product-1' => $product]);

        $this->assertSame(0.0, $mapped['ecommerce']['tax']);
        $this->assertSame(0.0, $mapped['ecommerce']['shipping']);
        $this->assertSame(11.60, $mapped['ecommerce']['value']);
        $this->assertSame('10001', $mapped['ecommerce']['transaction_id']);
        $this->assertSame('Winzer', $mapped['ecommerce']['items'][0]['item_brand']);
        $this->assertSame('Rotwein', $mapped['ecommerce']['items'][0]['item_category']);
    }

    private function wineProduct(): SalesChannelProductEntity
    {
        $manufacturer = new ProductManufacturerEntity();
        $manufacturer->setTranslated(['name' => 'Winzer']);

        $category = new CategoryEntity();
        $category->setTranslated(['name' => 'Rotwein']);

        $product = new SalesChannelProductEntity();
        $product->setProductNumber('W-1');
        $product->setTranslated(['name' => 'Riesling']);
        $product->setManufacturer($manufacturer);
        $product->setSeoCategory($category);
        $product->setVariation([
            ['group' => 'Jahrgang', 'option' => '2020'],
            ['group' => 'Flaschengröße', 'option' => '0,75 l'],
        ]);
        $product->setProperties(new PropertyGroupOptionCollection([
            $this->property('Rebsorte', 'Riesling'),
            $this->property('Anbaugebiet', 'Mosel'),
            $this->property('Jahrgang', '2020'),
            $this->property('Flaschengröße', '0,75 l'),
        ]));
        $product->setCalculatedPrice($this->price(11.60, 11.60));

        return $product;
    }

    private function property(string $groupName, string $optionName): PropertyGroupOptionEntity
    {
        $group = new PropertyGroupEntity();
        $group->setTranslated(['name' => $groupName]);

        $option = new PropertyGroupOptionEntity();
        $option->setId(md5($groupName . $optionName));
        $option->setGroupId($groupName === 'Flaschengröße' ? 'group-size' : md5($groupName));
        $option->setTranslated(['name' => $optionName]);
        $option->setGroup($group);

        return $option;
    }

    private function price(float $unit, float $total): CalculatedPrice
    {
        return new CalculatedPrice($unit, $total, new CalculatedTaxCollection(), new TaxRuleCollection());
    }

    private function context(): SalesChannelContext
    {
        $currency = new CurrencyEntity();
        $currency->setIsoCode('EUR');

        $salesChannel = new SalesChannelEntity();
        $salesChannel->setTranslated(['name' => 'Weinshop']);

        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getCurrency')->willReturn($currency);
        $context->method('getSalesChannel')->willReturn($salesChannel);

        return $context;
    }
}
