<?php declare(strict_types=1);

namespace Nf\Booking\Subscriber;

use Nf\Booking\Service\Booking\BookingServiceInterface;
use Nf\Booking\Service\Booking\CreditServiceInterface;
use Nf\Booking\Service\Booking\UserServiceInterface;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\System\SalesChannel\Event\SalesChannelContextRestoredEvent;
use Shopware\Core\System\SalesChannel\Event\SalesChannelContextTokenChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Customer\Event\CustomerLoginEvent;

readonly class BookingSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private BookingServiceInterface $bookingService,
        private UserServiceInterface    $userService,
        private CreditServiceInterface  $creditService,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'onOrderPlaced',
            CustomerLoginEvent::class => 'onCustomerLogin',
            SalesChannelContextTokenChangeEvent::class => 'onTokenChange',
            SalesChannelContextRestoredEvent::class => 'onTokenRestored'
        ];
    }

    /**
     * @throws \Exception
     */
    public function onOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        $order = $event->getOrder();
        $lineItems = $order->getLineItems();
        $context = $event->getContext();

        if ($lineItems === null) return;

        foreach ($lineItems as $item) {
            $payload = $item->getPayload();

            if (isset($payload['nfBookingId'])) {
                $booking = $this->bookingService->confirmReservation(
                    $payload['nfBookingId'],
                    $order,
                    $context
                );

                if ($booking->getTotalCreditSlots()) {
                    $this->creditService->confirmUsage($booking, $context);
                }
            }
        }
    }

    public function onTokenChange(SalesChannelContextTokenChangeEvent $event): void
    {
        $newToken = $event->getCurrentToken();
        $oldToken = $event->getPreviousToken();

        $this->userService->updateUser($oldToken, $newToken, null, $event->getContext());
    }

    public function onTokenRestored(SalesChannelContextRestoredEvent $event): void
    {
        $newToken = $event->getRestoredSalesChannelContext()->getToken();
        $oldToken = $event->getCurrentSalesChannelContext()->getToken();

        $this->userService->updateUser($oldToken, $newToken, null, $event->getContext());
    }


    public function onCustomerLogin(CustomerLoginEvent $event): void
    {
        $oldToken = $event->getContextToken();
        $customer = $event->getCustomer();

        $this->userService->updateUser($oldToken, null, $customer->getId(), $event->getContext());
    }


}