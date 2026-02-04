<?php declare(strict_types=1);

namespace Nf\OrderDay\Subscriber;

use Shopware\Core\Checkout\Cart\Order\CartConvertedEvent;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Nf\OrderDay\Service\OrderDayService;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly OrderDayService $orderDayService
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutLoaded',
            CartConvertedEvent::class => 'onCartConverted',
            'framework.validation.order.create' => 'onBuildValidation',
        ];
    }

    public function onCheckoutLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $orderDay = $this->orderDayService->getAvailableDays($event->getSalesChannelContext());
        $page = $event->getPage();
        $page->addExtension('orderDay', $orderDay);
    }

    public function onCartConverted(CartConvertedEvent $event): void
    {
        $cart = $event->getCart();
        $order = $event->getConvertedCart();
        $context = $event->getSalesChannelContext();
        $order = $this->orderDayService->updateOrderDay($order, $cart, $context);
        $event->setConvertedCart($order);
    }

    public function onBuildValidation(BuildValidationEvent $event): void
    {
        $definition = $event->getDefinition();
        $definition->add('nf-order-day', new NotBlank());
    }
}
