<?php declare(strict_types=1);

namespace Nf\CustomFinishPage\Service;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\ListPrice;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;

trait DiscountCalculatorTrait
{
    public function applyDiscount(SalesChannelProductEntity $product, float $discountPercent, \DateTimeInterface $endedAt): void
    {
        $discountFactor = 1 - ($discountPercent / 100);

        $newPrice = $this->calculateNewPrice($product->getCalculatedPrice(), $discountFactor);
        $product->setCalculatedPrice($newPrice);

        $prices = $product->getCalculatedPrices();
        foreach ($prices as $key => $price) {
            $prices->set($key, $this->calculateNewPrice($price, $discountFactor));
        }

        $product->addExtension('upsell_active', new \Shopware\Core\Framework\Struct\ArrayStruct([
            'discount' => $discountPercent,
            'endedAt' => $endedAt
        ]));
    }

    private function calculateNewPrice(CalculatedPrice $price, float $discountFactor): CalculatedPrice
    {
        $originalPrice = $price->getUnitPrice();
        $newUnitPrice = round($originalPrice * $discountFactor, 2);

        return new CalculatedPrice(
            $newUnitPrice,
            $newUnitPrice * $price->getQuantity(),
            $price->getCalculatedTaxes(),
            $price->getTaxRules(),
            $price->getQuantity(),
            null,
            ListPrice::createFromUnitPrice($newUnitPrice, $originalPrice)
        );
    }
}