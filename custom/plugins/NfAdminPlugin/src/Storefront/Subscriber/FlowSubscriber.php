<?php declare(strict_types=1);

namespace Nf\AdminPlugin\Storefront\Subscriber;

use Shopware\Core\Content\Flow\Events\FlowSendMailActionEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * Class FlowSubscriber
 * @package Nf\AdminPlugin\Storefront\Subscriber
 */
class FlowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityRepository $userRepository
    ) {}

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FlowSendMailActionEvent::class => 'onFlowSendMail',
        ];
    }


    public function onFlowSendMail(FlowSendMailActionEvent $event): void
    {
        $flow = $event->getStorableFlow();
        $recipient = $flow->getConfig()['recipient'] ?? null;
        if (isset($recipient['type']) && str_starts_with($recipient['type'], 'role_')) {
            $recipients = [];
            $roleId = substr($recipient['type'], strlen('role_'));

            $context = $event->getContext();
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('aclRoles.id',$roleId));

            $users = $this->userRepository->search($criteria, $context)->getElements();
            foreach ($users as $user) {
                $recipients[$user->getEmail()] = $user->getFirstName() . ' ' . $user->getLastName();
            }

            $data = $event->getDataBag();
            $data->set('recipients', $recipients);
        }
    }

}