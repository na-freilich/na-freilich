<?php declare(strict_types=1);

namespace Nf\CustomFinishPage\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Nf\CustomFinishPage\Service\UpsellPriceService;

class NfUpsellCartProcessor implements CartProcessorInterface
{
    public function __construct(
        private readonly QuantityPriceCalculator $calculator,
        private readonly UpsellPriceService $upsellPriceService
    ) {

    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $lineItems = $toCalculate->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        foreach ($lineItems as $lineItem) {
            $upsellData = $this->upsellPriceService->getActiveDiscount($lineItem->getReferencedId(), $context);

            if ($upsellData) {
                $this->applyPriceDiscount($lineItem, $upsellData->get('amount'), $context);
            }
        }
    }

    private function applyPriceDiscount(LineItem $lineItem, float $discountPercent, SalesChannelContext $context): void
    {
        $priceDefinition = $lineItem->getPriceDefinition();
        if (!$priceDefinition instanceof QuantityPriceDefinition) {
            return;
        }

        $originalPrice = $lineItem->getPayloadValue('original_unit_price');

        if ($originalPrice === null) {
            $originalPrice = $priceDefinition->getPrice();
            $lineItem->setPayloadValue('original_unit_price', $originalPrice);
        }

        $targetPrice = (float)$originalPrice * (1 - ($discountPercent / 100));

        if (abs($priceDefinition->getPrice() - $targetPrice) < 0.0001) {
            return;
        }

        $newPriceDefinition = new QuantityPriceDefinition(
            $targetPrice,
            $priceDefinition->getTaxRules(),
            $priceDefinition->getQuantity()
        );

        $lineItem->setPriceDefinition($newPriceDefinition);
        $lineItem->setPrice($this->calculator->calculate($newPriceDefinition, $context));

        $lineItem->setPayloadValue('upsell_discount_applied', true);
    }
}