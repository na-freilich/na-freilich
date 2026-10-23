<?php declare(strict_types=1);

namespace Nf\AdminPlugin\Storefront\Subscriber;

use Shopware\Core\Content\Cookie\Event\CookieGroupCollectEvent;
use Shopware\Core\Content\Cookie\Service\CookieProvider;
use Shopware\Core\Content\Cookie\Struct\CookieEntry;
use Shopware\Core\Content\Cookie\Struct\CookieEntryCollection;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CookieSubscriber implements EventSubscriberInterface
{
    public const GTM_COOKIE = 'nf-google-tag-manager-enabled';
    public const MARKER_COOKIE = 'nf-marker-io-enabled';

    public function __construct(
        private readonly SystemConfigService $configService
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
        $group = $event->cookieGroupCollection->get(CookieProvider::SNIPPET_NAME_COOKIE_GROUP_MARKETING);
        if ($group === null) {
            return;
        }

        $entries = $group->getEntries() ?? new CookieEntryCollection();
        $group->setEntries($entries);

        $gtmId = trim($this->configService->getString('AdminPlugin.config.gtmId', $salesChannelId));
        if ($gtmId !== '') {
            $this->addCookie(
                $entries,
                self::GTM_COOKIE,
                'nfAdmin.cookie.googleTagManager',
                'nfAdmin.cookie.googleTagManagerDescription'
            );
        }

        $markerEnabled = $this->configService->getBool('AdminPlugin.config.markerIoEnabled', $salesChannelId);
        $markerProject = trim($this->configService->getString('AdminPlugin.config.markerIoProject', $salesChannelId));
        if ($markerEnabled && $markerProject !== '') {
            $this->addCookie(
                $entries,
                self::MARKER_COOKIE,
                'nfAdmin.cookie.markerIo',
                'nfAdmin.cookie.markerIoDescription'
            );
        }
    }

    private function addCookie(CookieEntryCollection $entries, string $cookie, string $name, string $description): void
    {
        foreach ($entries as $entry) {
            if ($entry->cookie === $cookie) {
                return;
            }
        }

        $cookieEntry = new CookieEntry($cookie);
        $cookieEntry->name = $name;
        $cookieEntry->description = $description;
        $cookieEntry->value = '1';
        $cookieEntry->expiration = 30;

        $entries->add($cookieEntry);
    }
}
