<?php declare(strict_types=1);

namespace Nf\AdminPlugin\Service;

use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\Tax\TaxEntity;

class ProductService
{
    private const CONFIG_RECALCULATE_GROSS = 'AdminPlugin.config.recalculateGrossPrices';

    public function __construct(
        private readonly SystemConfigService $configService
    ) {
    }

    public function applyCustomerGross(ProductEntity $product, SalesChannelContext $context): void
    {
        $taxRate = $this->resolveTaxRate($product, $context);
        if ($taxRate === null || $this->matchesProductTax($product, $taxRate)) {
            return;
        }

        $this->rewritePrices($product->getPrice(), $taxRate);

        foreach ($product->getPrices() ?? [] as $advancedPrice) {
            $this->rewritePrices($advancedPrice->getPrice(), $taxRate);
        }
    }

    public function resolveTaxRate(ProductEntity $product, SalesChannelContext $context): ?float
    {
        if (!$this->isEnabled($context) || $context->getCustomer() === null) {
            return null;
        }

        $taxId = $product->getTaxId();
        if ($taxId === null) {
            return null;
        }

        $tax = $context->getTaxRules()->get($taxId);
        if (!$tax instanceof TaxEntity) {
            return null;
        }

        $rule = $tax->getRules()?->first();
        if ($rule === null || $rule === false) {
            return null;
        }

        return $rule->getTaxRate();
    }

    public function resolveCurrencyPrice(ProductEntity $product, SalesChannelContext $context, int $quantity): ?Price
    {
        $advancedPrices = $this->matchingAdvancedPrices($product, $context);
        if ($advancedPrices !== null) {
            $chosen = null;
            foreach ($advancedPrices as $advancedPrice) {
                $chosen = $advancedPrice;
                $end = $advancedPrice->getQuantityEnd();
                if ($end === null || $quantity <= $end) {
                    break;
                }
            }

            if ($chosen instanceof ProductPriceEntity) {
                return $chosen->getPrice()->getCurrencyPrice($context->getCurrencyId());
            }
        }

        return $product->getPrice()?->getCurrencyPrice($context->getCurrencyId());
    }

    public function grossForContext(Price $price, float $taxRate, SalesChannelContext $context): float
    {
        $gross = $this->grossFromNet($price->getNet(), $taxRate);
        if ($price->getCurrencyId() !== $context->getCurrencyId()) {
            $gross *= $context->getContext()->getCurrencyFactor();
        }

        return $gross;
    }

    public function grossFromNet(float $net, float $taxRate): float
    {
        return round($net * (1 + $taxRate / 100), 2);
    }

    private function isEnabled(SalesChannelContext $context): bool
    {
        return $this->configService->getBool(self::CONFIG_RECALCULATE_GROSS, $context->getSalesChannelId());
    }

    private function matchesProductTax(ProductEntity $product, float $taxRate): bool
    {
        $productTaxRate = $product->getTax()?->getTaxRate();

        return $productTaxRate !== null && abs($productTaxRate - $taxRate) < 0.00001;
    }

    private function rewritePrices(?PriceCollection $prices, float $taxRate): void
    {
        if ($prices === null) {
            return;
        }

        foreach ($prices as $price) {
            $price->setGross($this->grossFromNet($price->getNet(), $taxRate));

            $listPrice = $price->getListPrice();
            if ($listPrice !== null) {
                $listPrice->setGross($this->grossFromNet($listPrice->getNet(), $taxRate));
            }
        }
    }

    /**
     * @return iterable<ProductPriceEntity>|null
     */
    private function matchingAdvancedPrices(ProductEntity $product, SalesChannelContext $context): ?iterable
    {
        $prices = $product->getPrices();
        if ($prices === null || $prices->count() === 0 || $context->getRuleIds() === []) {
            return null;
        }

        $availableRuleIds = [];
        foreach ($prices as $price) {
            $availableRuleIds[$price->getRuleId()] = true;
        }

        foreach ($context->getRuleIds() as $ruleId) {
            if (!isset($availableRuleIds[$ruleId])) {
                continue;
            }

            $matched = $prices->filter(
                static fn (ProductPriceEntity $price): bool => $price->getRuleId() === $ruleId
            )->getElements();

            usort(
                $matched,
                static fn (ProductPriceEntity $a, ProductPriceEntity $b): int => $a->getQuantityStart() <=> $b->getQuantityStart()
            );

            return $matched;
        }

        return null;
    }
}
