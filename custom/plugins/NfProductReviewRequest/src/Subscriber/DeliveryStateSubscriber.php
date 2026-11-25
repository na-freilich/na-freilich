<?php declare(strict_types=1);

namespace Nf\ProductReviewRequest\Subscriber;

use Nf\ProductReviewRequest\Service\FollowUpScheduleService;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DeliveryStateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly FollowUpScheduleService $followUpScheduleService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StateMachineTransitionEvent::class => 'onTransition',
        ];
    }

    public function onTransition(StateMachineTransitionEvent $event): void
    {
        $this->followUpScheduleService->onDeliveryTransition($event);
    }
}
