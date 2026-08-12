<?php declare(strict_types=1);

namespace Nf\Booking\Subscriber;

use Nf\Booking\Service\Booking\BookingServiceInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\Event\StateMachineStateChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\Struct\ArrayEntity;

class OrderPaidSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityRepository        $orderTransactionRepository,
        private readonly BookingServiceInterface $bookingService,
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_machine.order_transaction.state_changed' => 'onOrderTransactionStateChange',
            OrderEvents::ORDER_LOADED_EVENT => 'onOrdersLoaded'
        ];
    }

    public function onOrdersLoaded(EntityLoadedEvent $event): void
    {
        foreach ($event->getEntities() as $order) {
            $cancellationHours = $this->systemConfigService->getInt('NfBooking.config.cancellationTimeout') ?: 24;

            $orderDateTime = $order->getOrderDateTime();
            if (!$orderDateTime) {
                continue;
            }

            $clonedDateTime = clone $orderDateTime;
            $cancellationDeadline = $clonedDateTime->modify('+' . $cancellationHours . ' hours');
            $now = new \DateTime();

            $technicalName = $order->getStateMachineState() ? $order->getStateMachineState()->getTechnicalName() : '';
            $isAllowedState = !in_array($technicalName, ['cancelled', 'completed'], true);

            $isCancellable = ($now < $cancellationDeadline) && $isAllowedState;
            $cancellableItems = [];

            if ($isCancellable)
            {
                $lineItems = $order->getLineItems();
                if (!$lineItems) {
                    continue;
                }

                foreach ($lineItems as $item) {
                    $payload = $item->getPayload();

                    if ($payload !== null && isset($payload['nfBookingId'])) {
                        $cancellableItems[] = [
                            'id' => $item->getId(),
                            'label' => $item->getLabel(),
                            'quantity' => $item->getQuantity(),
                            'bookingId' => $payload['nfBookingId']
                        ];
                        break;
                    }
                }
            }
            if (!empty($cancellableItems)) {
                $order->addExtension('customBookingCancellation', new ArrayEntity([
                    'isCancellable' => true,
                    'items' => $cancellableItems
                ]));
            }
        }
    }

    public function onOrderTransactionStateChange(StateMachineStateChangeEvent $event): void
    {
        if ($event->getNextState()->getTechnicalName() !== 'paid') {
            return;
        }

        $transactionId = $event->getTransition()->getEntityId();
        $context = $event->getContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $transactionId));
        $criteria->addAssociation('order.customer');
        $criteria->addAssociation('order.lineItems');

        /** @var OrderTransactionEntity|null $transaction */
        $transaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        if (!$transaction || !$transaction->getOrder()) {
            return;
        }

        $order = $transaction->getOrder();

        $lineItems = $order->getLineItems();
        $context = $event->getContext();

        if ($lineItems === null)
            return;

        foreach ($lineItems as $item) {
            $payload = $item->getPayload();

            if (isset($payload['nfBookingId'])) {
                $this->bookingService->confirmPaidReservation(
                    $payload['nfBookingId'],
                    $order,
                    $context
                );
            }
        }
    }
}