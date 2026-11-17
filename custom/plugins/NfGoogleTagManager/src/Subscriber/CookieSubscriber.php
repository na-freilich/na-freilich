<?php declare(strict_types=1);

namespace NfGoogleTagManager\Subscriber;

use Shopware\Core\Content\Cookie\Event\CookieGroupCollectEvent;
use Shopware\Core\Content\Cookie\Service\CookieProvider;
use Shopware\Core\Content\Cookie\Struct\CookieEntry;
use Shopware\Core\Content\Cookie\Struct\CookieEntryCollection;
use Shopware\Core\Content\Cookie\Struct\CookieGroup;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CookieSubscriber implements EventSubscriberInterface
{
    public const COOKIE_NAME = 'nf-gtm-analytics';

    public function __construct(
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CookieGroupCollectEvent::class => 'onCookieGroupCollect',
        ];
    }

    public function onCookieGroupCollect(CookieGroupCollectEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();

        if ($this->systemConfigService->getBool('NfGoogleTagManager.config.externalConsent', $salesChannelId)) {
            return;
        }

        if (!$this->systemConfigService->getBool('NfGoogleTagManager.config.active', $salesChannelId)) {
            return;
        }

        $gtmId = trim($this->systemConfigService->getString('NfGoogleTagManager.config.gtmId', $salesChannelId));
        if ($gtmId === '') {
            return;
        }

        $group = $event->cookieGroupCollection->get(CookieProvider::SNIPPET_NAME_COOKIE_GROUP_STATISTICAL);
        if (!$group instanceof CookieGroup) {
            $group = new CookieGroup(CookieProvider::SNIPPET_NAME_COOKIE_GROUP_STATISTICAL);
            $group->description = 'cookie.groupStatisticalDescription';
            $group->setEntries(new CookieEntryCollection());
            $event->cookieGroupCollection->add($group);
        }

        if ($group->getCookie() !== null) {
            return;
        }

        $entries = $group->getEntries();
        if ($entries === null) {
            $entries = new CookieEntryCollection();
            $group->setEntries($entries);
        }

        foreach ($entries as $entry) {
            if ($entry->cookie === self::COOKIE_NAME) {
                return;
            }
        }

        $cookieEntry = new CookieEntry(self::COOKIE_NAME);
        $cookieEntry->name = 'nfGoogleTagManager.cookie.statistics';
        $cookieEntry->description = 'nfGoogleTagManager.cookie.statisticsDescription';
        $cookieEntry->value = '1';
        $cookieEntry->expiration = 30;

        $entries->add($cookieEntry);
    }
}
