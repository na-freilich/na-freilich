<?php declare(strict_types=1);

namespace NfGoogleTagManager\Components;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class DataLayerMapper
{
    /**
     * @param array{item_list_id?: string, item_list_name?: string, item_category?: string, index?: int} $listContext
     * @param array<string, string> $propertyParameterByGroupId
     *
     * @return array<string, mixed>
     */
    public function mapProductItem(SalesChannelProductEntity $product, SalesChannelContext $context, int $quantity = 1, array $listContext = [], array $propertyParameterByGroupId = []): array
    {
        $calculatedPrice = $product->getCalculatedPrice();
        $unitPrice = $calculatedPrice ? $this->money($calculatedPrice->getUnitPrice()) : 0.0;

        $item = [
            'item_id' => $product->getProductNumber(),
            'item_name' => $product->getTranslation('name') ?? '',
            'affiliation' => $this->affiliation($context),
            'item_brand' => $this->manufacturerName($product),
            'item_category' => $this->categoryName($product, $listContext['item_category'] ?? ''),
            'price' => $unitPrice,
            'quantity' => $quantity,
        ];

        $variant = $this->variantLabel($product->getVariation());
        if ($variant !== '') {
            $item['item_variant'] = $variant;
        }
        foreach ($this->configuredProperties($product, $propertyParameterByGroupId) as $parameter => $value) {
            $item[$parameter] = $value;
        }

        $listId = trim((string) ($listContext['item_list_id'] ?? ''));
        $listName = trim((string) ($listContext['item_list_name'] ?? ''));
        if ($listId !== '') {
            $item['item_list_id'] = $listId;
        }
        if ($listName !== '') {
            $item['item_list_name'] = $listName;
        }
        if (isset($listContext['index']) && $listContext['index'] > 0) {
            $item['index'] = $listContext['index'];
        }

        if ($calculatedPrice !== null) {
            $this->applyUnitDiscount($item, $calculatedPrice);
        }

        return $item;
    }

    /**
     * @param array<string, string> $propertyParameterByGroupId
     *
     * @return array<string, mixed>
     */
    public function mapViewItem(SalesChannelProductEntity $product, SalesChannelContext $context, array $propertyParameterByGroupId = []): array
    {
        $item = $this->mapProductItem($product, $context, 1, [], $propertyParameterByGroupId);

        return [
            'event' => 'view_item',
            'ecommerce' => $this->withAffiliation([
                'currency' => $context->getCurrency()->getIsoCode(),
                'value' => $item['price'],
                'items' => [$item],
            ], $context),
        ];
    }

    /**
     * @param iterable<SalesChannelProductEntity> $products
     * @param array<string, string> $propertyParameterByGroupId
     *
     * @return array<string, mixed>|null
     */
    public function mapItemList(iterable $products, SalesChannelContext $context, string $listId, string $listName, string $categoryFallback = '', array $propertyParameterByGroupId = []): ?array
    {
        $items = [];
        $index = 1;
        foreach ($products as $product) {
            if (!$product instanceof SalesChannelProductEntity) {
                continue;
            }
            $items[] = $this->mapProductItem($product, $context, 1, [
                'item_list_id' => $listId,
                'item_list_name' => $listName,
                'item_category' => $categoryFallback,
                'index' => $index,
            ], $propertyParameterByGroupId);
            ++$index;
        }

        if ($items === []) {
            return null;
        }

        return [
            'event' => 'view_item_list',
            'ecommerce' => $this->withAffiliation([
                'currency' => $context->getCurrency()->getIsoCode(),
                'item_list_id' => $listId,
                'item_list_name' => $listName,
                'items' => $items,
            ], $context),
        ];
    }

    /**
     * Goods value only. Shipping stays out of value. An empty cart is not an event.
     *
     * @param array<string, SalesChannelProductEntity> $productsById
     * @param array<string, string> $propertyParameterByGroupId
     *
     * @return array<string, mixed>|null
     */
    public function mapCart(Cart $cart, SalesChannelContext $context, array $productsById = [], array $propertyParameterByGroupId = []): ?array
    {
        $items = [];
        foreach ($cart->getLineItems() as $lineItem) {
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }

            $product = $productsById[$lineItem->getReferencedId() ?? ''] ?? null;
            $items[] = $this->mapReferencedItem(
                (string) ($lineItem->getReferencedId() ?? ''),
                (string) ($lineItem->getLabel() ?? ''),
                $lineItem->getQuantity(),
                $lineItem->getPrice(),
                $lineItem->getPayload(),
                $context,
                $product,
                $propertyParameterByGroupId
            );
        }

        if ($items === []) {
            return null;
        }

        $ecommerce = [
            'currency' => $context->getCurrency()->getIsoCode(),
            'value' => $this->money($cart->getPrice()->getPositionPrice()),
            'items' => $items,
        ];
        $this->applyPromotions($ecommerce, $cart->getLineItems());

        return [
            'event' => 'view_cart',
            'ecommerce' => $this->withAffiliation($ecommerce, $context),
        ];
    }

    /**
     * @param array<string, SalesChannelProductEntity> $productsById
     * @param array<string, string> $propertyParameterByGroupId
     *
     * @return array<string, mixed>
     */
    public function mapPurchase(OrderEntity $order, SalesChannelContext $context, array $productsById = [], array $propertyParameterByGroupId = []): array
    {
        $items = [];
        $lineItems = $order->getLineItems();
        if ($lineItems !== null) {
            foreach ($lineItems as $lineItem) {
                if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                    continue;
                }

                $product = $productsById[$lineItem->getReferencedId() ?? ''] ?? null;
                $items[] = $this->mapReferencedItem(
                    (string) ($lineItem->getReferencedId() ?? ''),
                    $lineItem->getLabel(),
                    $lineItem->getQuantity(),
                    $lineItem->getPrice(),
                    $lineItem->getPayload() ?? [],
                    $context,
                    $product,
                    $propertyParameterByGroupId,
                    $lineItem
                );
            }
        }

        $ecommerce = [
            'transaction_id' => $order->getOrderNumber(),
            'value' => $this->money($order->getAmountTotal()),
            'tax' => $this->money($order->getPrice()->getCalculatedTaxes()->getAmount()),
            'shipping' => $this->money($order->getShippingTotal()),
            'currency' => $context->getCurrency()->getIsoCode(),
            'items' => $items,
        ];
        if ($lineItems !== null) {
            $this->applyPromotions($ecommerce, $lineItems);
        }

        return [
            'event' => 'purchase',
            'ecommerce' => $this->withAffiliation($ecommerce, $context),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $propertyParameterByGroupId
     *
     * @return array<string, mixed>
     */
    private function mapReferencedItem(
        string $referencedId,
        string $label,
        int $quantity,
        ?CalculatedPrice $price,
        array $payload,
        SalesChannelContext $context,
        ?SalesChannelProductEntity $product,
        array $propertyParameterByGroupId = [],
        ?OrderLineItemEntity $orderLineItem = null
    ): array {
        if ($product instanceof SalesChannelProductEntity) {
            $item = $this->mapProductItem($product, $context, $quantity, [], $propertyParameterByGroupId);
        } else {
            $item = [
                'item_id' => (string) ($payload['productNumber'] ?? $referencedId),
                'item_name' => $label,
                'affiliation' => $this->affiliation($context),
                'item_brand' => '',
                'item_category' => '',
                'price' => 0.0,
                'quantity' => $quantity,
            ];
            $variant = $this->variantLabel(is_array($payload['options'] ?? null) ? $payload['options'] : []);
            if ($variant !== '') {
                $item['item_variant'] = $variant;
            }
        }

        $item['quantity'] = $quantity;
        if ($price !== null) {
            $item['price'] = $this->money($price->getUnitPrice());
            $this->applyUnitDiscount($item, $price);
        } elseif ($orderLineItem !== null) {
            $item['price'] = $this->money($orderLineItem->getUnitPrice());
        }

        return $item;
    }

    /**
     * @param array<string, mixed> $ecommerce
     * @param iterable<LineItem|OrderLineItemEntity> $lineItems
     */
    private function applyPromotions(array &$ecommerce, iterable $lineItems): void
    {
        $codes = [];
        $discount = 0.0;

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() !== LineItem::PROMOTION_LINE_ITEM_TYPE) {
                continue;
            }

            $payload = $lineItem->getPayload() ?? [];
            $code = $payload['code'] ?? null;
            if (is_string($code) && $code !== '') {
                $codes[] = $code;
            }

            $price = $lineItem->getPrice();
            if ($price !== null) {
                $discount += abs($price->getTotalPrice());
            }
        }

        if ($codes !== []) {
            $ecommerce['coupon'] = implode(',', array_unique($codes));
        }
        if ($discount > 0) {
            $ecommerce['discount'] = $this->money($discount);
        }
    }

    /**
     * @param array<string, mixed> $item
     */
    private function applyUnitDiscount(array &$item, CalculatedPrice $price): void
    {
        $listPrice = $price->getListPrice();
        if ($listPrice === null) {
            return;
        }

        $discount = $this->money($listPrice->getPrice() - $price->getUnitPrice());
        if ($discount > 0) {
            $item['discount'] = $discount;
        }
    }

    /**
     * @param array<int, mixed> $variation
     */
    private function variantLabel(array $variation): string
    {
        $parts = [];
        foreach ($variation as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $option = trim((string) ($entry['option'] ?? ''));
            if ($option === '') {
                continue;
            }
            $group = trim((string) ($entry['group'] ?? ''));
            $parts[] = $group !== '' ? $group . ': ' . $option : $option;
        }

        return implode(' / ', $parts);
    }

    /**
     * @param array<string, string> $propertyParameterByGroupId
     *
     * @return array<string, string>
     */
    private function configuredProperties(SalesChannelProductEntity $product, array $propertyParameterByGroupId): array
    {
        $properties = $product->getProperties();
        if ($properties === null || $propertyParameterByGroupId === []) {
            return [];
        }

        $values = [];
        foreach ($properties as $option) {
            $parameter = $propertyParameterByGroupId[$option->getGroupId()] ?? null;
            $value = trim((string) ($option->getTranslation('name') ?? ''));
            if ($parameter === null || $value === '') {
                continue;
            }

            $values[$parameter] = $this->appendValue($values[$parameter] ?? null, $value);
        }

        return $values;
    }

    private function appendValue(?string $current, string $value): string
    {
        if ($current === null || $current === '') {
            return $value;
        }
        if (str_contains($current, $value)) {
            return $current;
        }

        return $current . ', ' . $value;
    }

    private function manufacturerName(SalesChannelProductEntity $product): string
    {
        $manufacturer = $product->getManufacturer();
        if ($manufacturer === null) {
            return '';
        }

        return (string) ($manufacturer->getTranslation('name') ?? '');
    }

    private function categoryName(SalesChannelProductEntity $product, string $fallback): string
    {
        $seoCategory = $product->getSeoCategory();
        if ($seoCategory !== null) {
            $name = $seoCategory->getTranslation('name');
            if (is_string($name) && $name !== '') {
                return $name;
            }
        }

        $categories = $product->getCategories();
        if ($categories !== null && $categories->count() > 0) {
            $firstCategory = $categories->first();
            $name = $firstCategory ? $firstCategory->getTranslation('name') : null;
            if (is_string($name) && $name !== '') {
                return $name;
            }
        }

        return $fallback;
    }

    private function affiliation(SalesChannelContext $context): string
    {
        $salesChannel = $context->getSalesChannel();
        $name = $salesChannel->getTranslation('name');
        if (is_string($name) && $name !== '') {
            return $name;
        }

        return (string) ($salesChannel->getName() ?? '');
    }

    /**
     * @param array<string, mixed> $ecommerce
     *
     * @return array<string, mixed>
     */
    private function withAffiliation(array $ecommerce, SalesChannelContext $context): array
    {
        $affiliation = $this->affiliation($context);
        if ($affiliation !== '') {
            $ecommerce['affiliation'] = $affiliation;
        }

        return $ecommerce;
    }

    private function money(float $amount): float
    {
        return round($amount, 2);
    }
}
