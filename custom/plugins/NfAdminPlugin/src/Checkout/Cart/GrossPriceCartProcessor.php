<?php declare(strict_types=1);

namespace Nf\AdminPlugin\Checkout\Cart;

use Nf\AdminPlugin\Service\ProductService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\CheckoutPermissions;
use Shopware\Core\Content\Product\Cart\ProductCartProcessor;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class GrossPriceCartProcessor implements CartProcessorInterface
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly QuantityPriceCalculator $calculator
    ) {
    }

    public function process(
        CartDataCollection $data,
        Cart $original,
        Cart $toCalculate,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        if ($context->getTaxState() !== CartPrice::TAX_STATE_GROSS) {
            return;
        }

        foreach ($toCalculate->getLineItems()->filterFlatByType(LineItem::PRODUCT_LINE_ITEM_TYPE) as $lineItem) {
            $this->recalculateLineItem($lineItem, $data, $context, $behavior);
        }
    }

    private function recalculateLineItem(
        LineItem $lineItem,
        CartDataCollection $data,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        if ($this->keepsExistingPrice($lineItem, $behavior)) {
            return;
        }

        $definition = $lineItem->getPriceDefinition();
        if (!$definition instanceof QuantityPriceDefinition) {
            return;
        }

        $referencedId = $lineItem->getReferencedId();
        if ($referencedId === null) {
            return;
        }

        $product = $data->get('product-' . $referencedId);
        if (!$product instanceof ProductEntity) {
            return;
        }

        $taxRate = $this->productService->resolveTaxRate($product, $context);
        if ($taxRate === null) {
            return;
        }

        $productTaxRate = $product->getTax()?->getTaxRate();
        if ($productTaxRate !== null && abs($productTaxRate - $taxRate) < 0.00001) {
            return;
        }

        $currencyPrice = $this->productService->resolveCurrencyPrice($product, $context, $lineItem->getQuantity());
        if ($currencyPrice === null) {
            return;
        }

        $gross = $this->productService->grossForContext($currencyPrice, $taxRate, $context);
        if (abs($definition->getPrice() - $gross) < 0.001) {
            return;
        }

        $replacement = new QuantityPriceDefinition($gross, $definition->getTaxRules(), $definition->getQuantity());
        $replacement->setReferencePriceDefinition($definition->getReferencePriceDefinition());
        $replacement->setRegulationPrice($definition->getRegulationPrice());

        $listPrice = $currencyPrice->getListPrice();
        if ($listPrice !== null) {
            $replacement->setListPrice($this->productService->grossForContext($listPrice, $taxRate, $context));
        } else {
            $replacement->setListPrice($definition->getListPrice());
        }

        $lineItem->setPriceDefinition($replacement);
        $lineItem->setPrice($this->calculator->calculate($replacement, $context));
    }

    private function keepsExistingPrice(LineItem $lineItem, CartBehavior $behavior): bool
    {
        if ($lineItem->getPriceDefinition() !== null
            && $lineItem->hasExtension(ProductCartProcessor::CUSTOM_PRICE)
            && $behavior->hasPermission(CheckoutPermissions::ALLOW_PRODUCT_PRICE_OVERWRITES)
        ) {
            return true;
        }

        if ($lineItem->getPriceDefinition() !== null && $behavior->hasPermission(CheckoutPermissions::SKIP_PRODUCT_RECALCULATION)) {
            return true;
        }

        return $lineItem->isModifiedByApp();
    }
}
