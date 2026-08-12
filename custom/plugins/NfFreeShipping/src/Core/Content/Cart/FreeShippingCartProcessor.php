<?php declare(strict_types=1);

namespace NfFreeShipping\Core\Content\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryInformation;
use Shopware\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;

class FreeShippingCartProcessor implements CartProcessorInterface
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function process(
        CartDataCollection $data,
        Cart $original,
        Cart $toCalculate,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        $salesChannelId = $context->getSalesChannelId();

        if (!$this->systemConfigService->get('NfFreeShipping.config.active', $salesChannelId)) {
            return;
        }

        $selectedMethods = $this->systemConfigService->get('NfFreeShipping.config.shippingMethods', $salesChannelId) ?? [];
        $threshold = (float) $this->systemConfigService->get('NfFreeShipping.config.thresholdAmount', $salesChannelId);

        $currentShippingMethodId = $context->getShippingMethod()->getId();

        if (!empty($selectedMethods) && !in_array($currentShippingMethodId, $selectedMethods, true)) {
            return;
        }

        $productTotal = 0.0;
        foreach ($toCalculate->getLineItems() as $lineItem) {
            if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
                $productTotal += $lineItem->getPrice()->getTotalPrice();
            }
        }

        if ($productTotal >= $threshold && $toCalculate->getDeliveries()->count() > 0) {

            foreach ($toCalculate->getDeliveries() as $delivery) {
                $zeroPrice = new CalculatedPrice(
                    0.0,
                    0.0,
                    new CalculatedTaxCollection(),
                    new TaxRuleCollection()
                );

                $delivery->setShippingCosts($zeroPrice);
            }
        }
    }
}