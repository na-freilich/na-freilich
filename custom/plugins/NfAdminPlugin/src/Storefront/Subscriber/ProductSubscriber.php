<?php declare(strict_types=1);

namespace Nf\AdminPlugin\Storefront\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Nf\AdminPlugin\Service\ProductService;

/**
 * Class ProductPageSubscriber
 * @package Nf\LastViewedProducts\Storefront\Subscriber
 */
class ProductSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_LOADED_EVENT => 'onProductLoaded'
        ];
    }


    public function onProductLoaded(EntityLoadedEvent $event): void
    {
        $loadedProducts = $event->getEntities();
        foreach ($loadedProducts as $product) {
            $this->productService->updatePrice($product);
        }
    }

}