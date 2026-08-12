<?php declare(strict_types=1);

namespace Nf\CustomFinishPage\Storefront\Subscriber;

use Nf\CustomFinishPage\Service\UpsellPriceService;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Cart\Price\Struct\ListPrice;
use Shopware\Core\Framework\Struct\ArrayStruct;
class ProductPriceSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UpsellPriceService $upsellPriceService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'sales_channel.product.loaded' => 'onProductsLoaded'
        ];
    }

    public function onProductsLoaded(EntityLoadedEvent $event): void
    {

        $salesChannelContext = $event->getSalesChannelContext();

        /** @var SalesChannelProductEntity $product */
        foreach ($event->getEntities() as $product) {
            $upsellData = $this->upsellPriceService->getActiveDiscount(
                $product->getId(),
                $salesChannelContext
            );

            if ($upsellData) {
                $discount = $upsellData->get('amount');
                $endedAt = $upsellData->get('endedAt');

                $this->applyDiscount($product, $discount);
                $product->addExtension('upsell_active', new \Shopware\Core\Framework\Struct\ArrayStruct([
                    'discount' => $discount,
                    'endedAt' => $endedAt
                ]));
            }
        }
    }

    private function applyDiscount(SalesChannelProductEntity $product, float $discountPercent): void
    {
        $mainPrice = $product->getCalculatedPrice();
        $product->setCalculatedPrice($this->calculateNewPrice($mainPrice, $discountPercent));

        $advancedPrices = $product->getCalculatedPrices();
        if ($advancedPrices->count() > 0) {
            foreach ($advancedPrices as $key => $price) {
                $discountedPrice = $this->calculateNewPrice($price, $discountPercent);
                $advancedPrices->set($key, $discountedPrice);
            }
        }
    }

    private function calculateNewPrice(CalculatedPrice $price, float $discountPercent): CalculatedPrice
    {
        $originalUnitPrice = $price->getUnitPrice();
        $newUnitPrice = $originalUnitPrice * (1 - ($discountPercent / 100));

        $listPrice = ListPrice::createFromUnitPrice(
            $newUnitPrice,
            $originalUnitPrice
        );

        return new CalculatedPrice(
            $newUnitPrice,
            $newUnitPrice * $price->getQuantity(),
            $price->getCalculatedTaxes(),
            $price->getTaxRules(),
            $price->getQuantity(),
            null,
            $listPrice
        );
    }
}