<?php declare(strict_types=1);

namespace NfGoogleTagManager\Components;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Cart\Cart;

class DataLayerMapper
{
    public function mapProductItem(ProductEntity $product, SalesChannelContext $context, int $quantity = 1): array
    {
        $calculatedPrice = $product->getCalculatedPrice();
        $unitPrice = $calculatedPrice ? $calculatedPrice->getUnitPrice() : 0.0;

        $brand = '';
        if ($product->getManufacturer()) {
            $brand = $product->getManufacturer()->getTranslation('name') ?? '';
        }

        $categoryName = '';

        if ($product->getSeoCategory()) {
            $categoryName = $product->getSeoCategory()->getTranslation('name') ?? '';
        } elseif ($product->getCategories() && $product->getCategories()->count() > 0) {
            $firstCategory = $product->getCategories()->first();
            if ($firstCategory) {
                $categoryName = $firstCategory->getTranslation('name') ?? '';
            }
        }

        return [
            'item_id' => $product->getProductNumber(),
            'item_name' => $product->getTranslation('name') ?? '',
            'item_brand' => $brand,
            'item_category' => $categoryName,
            'price' => $unitPrice,
            'quantity' => $quantity,
        ];
    }

    public function mapViewItem(ProductEntity $product, SalesChannelContext $context): array
    {
        $item = $this->mapProductItem($product, $context);

        return [
            'event' => 'view_item',
            'ecommerce' => [
                'currency' => $context->getCurrency()->getIsoCode(),
                'value' => $item['price'],
                'items' => [$item]
            ]
        ];
    }

    public function mapPurchase(OrderEntity $order, SalesChannelContext $context): array
    {
        $items = [];
        $lineItems = $order->getLineItems();

        if ($lineItems) {
            foreach ($lineItems as $lineItem) {
                $payload = $lineItem->getPayload();

                $items[] = [
                    'item_id' => $lineItem->getPayload()['productNumber'] ?? $lineItem->getReferencedId(),
                    'item_name' => $lineItem->getLabel(),
                    'price' => $lineItem->getUnitPrice(),
                    'quantity' => $lineItem->getQuantity(),
                    'item_brand' => $payload['manufacturerName'] ?? '',
                    'item_category' => '',
                ];
            }
        }

        return [
            'event' => 'purchase',
            'ecommerce' => [
                'transaction_id' => $order->getOrderNumber(),
                'value' => $order->getAmountTotal(),
                'tax' => $order->getPrice()->getCalculatedTaxes()->getAmount(),
                'shipping' => $order->getShippingTotal(),
                'currency' => $context->getCurrency()->getIsoCode(),
                'items' => $items
            ]
        ];
    }

    public function mapCart(Cart $cart, SalesChannelContext $context): array
    {
        $items = [];

        foreach ($cart->getLineItems() as $lineItem) {
            if ($lineItem->getType() !== 'product') {
                continue;
            }

            $payload = $lineItem->getPayload();

            $items[] = [
                'item_id' => $payload['productNumber'] ?? $lineItem->getReferencedId(),
                'item_name' => $lineItem->getLabel(),
                'price' => $lineItem->getPrice()->getUnitPrice(),
                'quantity' => $lineItem->getQuantity(),
                'item_brand' => $payload['manufacturerName'] ?? '',
                'item_category' => '',
            ];
        }

        return [
            'event' => 'view_cart',
            'ecommerce' => [
                'currency' => $context->getCurrency()->getIsoCode(),
                'value' => $cart->getPrice()->getTotalPrice(),
                'items' => $items
            ]
        ];
    }
}