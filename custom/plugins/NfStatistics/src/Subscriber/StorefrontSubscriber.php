<?php

namespace Nf\Statistics\Subscriber;

use Nf\Statistics\Service\CurrentUserService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Storefront\Page\GenericPageLoadedEvent;

class StorefrontSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private readonly CurrentUserService $currentUserService
    )
    {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
//            GenericPageLoadedEvent::class => 'onPageLoaded',
        ];
    }

    public function onPageLoaded(GenericPageLoadedEvent $event)
    {
        $context = $event->getSalesChannelContext();
        $request = $event->getRequest();
        $this->currentUserService->updateStatistic($context, $request);
    }


}
