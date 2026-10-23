<?php declare(strict_types=1);

namespace Nf\AdminPlugin\Storefront\Subscriber;

use Nf\AdminPlugin\Service\ProductService;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ProductService $productService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sales_channel.' . ProductEvents::PRODUCT_LOADED_EVENT => ['onSalesChannelProductLoaded', 100],
        ];
    }

    public function onSalesChannelProductLoaded(SalesChannelEntityLoadedEvent $event): void
    {
        foreach ($event->getEntities() as $product) {
            if (!$product instanceof ProductEntity) {
                continue;
            }

            $this->productService->applyCustomerGross($product, $event->getSalesChannelContext());
        }
    }
}
