<?php declare(strict_types=1);

namespace Nf\ProductReviewRequest\Subscriber;

use Nf\ProductReviewRequest\Service\OrderConsentService;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class OrderSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly OrderConsentService $orderConsentService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'onOrderPlaced',
        ];
    }

    public function onOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        $this->orderConsentService->storeConsent($event);
    }
}
