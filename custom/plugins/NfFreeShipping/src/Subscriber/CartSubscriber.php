<?php declare(strict_types=1);

namespace NfFreeShipping\Subscriber;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use  Shopware\Core\Checkout\Cart\LineItem\LineItem;
class CartSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SystemConfigService $systemConfigService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutCartPageLoadedEvent::class => 'onCartPageLoaded',
            OffcanvasCartPageLoadedEvent::class => 'onCartPageLoaded',
        ];
    }

    public function onCartPageLoaded(CheckoutCartPageLoadedEvent|OffcanvasCartPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();

        $isActive = $this->systemConfigService->get('NfFreeShipping.config.active', $salesChannelId);
        if (!$isActive) {
            return;
        }

        $selectedMethods = $this->systemConfigService->get('NfFreeShipping.config.shippingMethods', $salesChannelId) ?? [];
        $threshold = (float) $this->systemConfigService->get('NfFreeShipping.config.thresholdAmount', $salesChannelId);

        $currentShippingMethodId = $event->getSalesChannelContext()->getShippingMethod()->getId();

        if (empty($selectedMethods) || in_array($currentShippingMethodId, $selectedMethods, true)) {
            $cartTotal = 0.0;
            foreach ($event->getPage()->getCart()->getLineItems() as $lineItem) {
                if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
                    $cartTotal += $lineItem->getPrice()->getTotalPrice();
                }
            }

            $remaining = $threshold - $cartTotal;
            $percent = $threshold > 0 ? min(100, max(0, ($cartTotal / $threshold) * 100)) : 100;

            $event->getPage()->addExtension('nfFreeShipping', new \Shopware\Core\Framework\Struct\ArrayEntity([
                'threshold' => $threshold,
                'remaining' => max(0, $remaining),
                'percent' => $percent,
                'isFree' => $remaining <= 0,
            ]));
        }
    }
}