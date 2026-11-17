<?php declare(strict_types=1);

namespace NfGoogleTagManager\Tests\Subscriber;

use NfGoogleTagManager\Subscriber\CookieSubscriber;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Cookie\Event\CookieGroupCollectEvent;
use Shopware\Core\Content\Cookie\Service\CookieProvider;
use Shopware\Core\Content\Cookie\Struct\CookieEntryCollection;
use Shopware\Core\Content\Cookie\Struct\CookieGroup;
use Shopware\Core\Content\Cookie\Struct\CookieGroupCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;

class CookieSubscriberTest extends TestCase
{
    public function testAddsStatisticsCookieToTheEmptyCoreGroup(): void
    {
        $group = new CookieGroup(CookieProvider::SNIPPET_NAME_COOKIE_GROUP_STATISTICAL);
        $group->setEntries(new CookieEntryCollection());
        $collection = new CookieGroupCollection([$group]);

        $this->subscriber()->onCookieGroupCollect($this->event($collection));

        $entries = $collection->get(CookieProvider::SNIPPET_NAME_COOKIE_GROUP_STATISTICAL)?->getEntries();
        $this->assertNotNull($entries);
        $this->assertNotNull($entries->get(CookieSubscriber::COOKIE_NAME));
        $this->assertSame('1', $entries->get(CookieSubscriber::COOKIE_NAME)->value);
    }

    public function testCreatesTheStatisticsGroupWhenItIsMissing(): void
    {
        $collection = new CookieGroupCollection();

        $this->subscriber()->onCookieGroupCollect($this->event($collection));

        $group = $collection->get(CookieProvider::SNIPPET_NAME_COOKIE_GROUP_STATISTICAL);
        $this->assertInstanceOf(CookieGroup::class, $group);
        $this->assertSame('cookie.groupStatisticalDescription', $group->description);
        $this->assertNotNull($group->getEntries()?->get(CookieSubscriber::COOKIE_NAME));
    }

    public function testCreatesEntriesWhenTheGroupHasNone(): void
    {
        $group = new CookieGroup(CookieProvider::SNIPPET_NAME_COOKIE_GROUP_STATISTICAL);
        $collection = new CookieGroupCollection([$group]);

        $this->subscriber()->onCookieGroupCollect($this->event($collection));

        $this->assertNotNull($group->getEntries()?->get(CookieSubscriber::COOKIE_NAME));
    }

    public function testDoesNotRegisterAnEntryWhenTheGroupIsACookieItself(): void
    {
        $group = new CookieGroup(CookieProvider::SNIPPET_NAME_COOKIE_GROUP_STATISTICAL);
        $group->setCookie('statistics-group');
        $collection = new CookieGroupCollection([$group]);

        $this->subscriber()->onCookieGroupCollect($this->event($collection));

        $this->assertNull($group->getEntries());
        $this->assertSame('statistics-group', $group->getCookie());
    }

    public function testDoesNotAddTheCookieTwice(): void
    {
        $collection = new CookieGroupCollection();
        $subscriber = $this->subscriber();
        $event = $this->event($collection);

        $subscriber->onCookieGroupCollect($event);
        $subscriber->onCookieGroupCollect($event);

        $this->assertCount(1, $collection->get(CookieProvider::SNIPPET_NAME_COOKIE_GROUP_STATISTICAL)?->getEntries() ?? []);
    }

    public function testSkipsTheCookieWhenConsentIsManagedExternally(): void
    {
        $collection = new CookieGroupCollection();

        $this->subscriber(externalConsent: true)->onCookieGroupCollect($this->event($collection));

        $this->assertNull($collection->get(CookieProvider::SNIPPET_NAME_COOKIE_GROUP_STATISTICAL));
    }

    public function testSkipsTheCookieWhenTrackingIsInactive(): void
    {
        $collection = new CookieGroupCollection();

        $this->subscriber(active: false)->onCookieGroupCollect($this->event($collection));

        $this->assertNull($collection->get(CookieProvider::SNIPPET_NAME_COOKIE_GROUP_STATISTICAL));
    }

    public function testSkipsTheCookieWhenTheContainerIdIsEmpty(): void
    {
        $collection = new CookieGroupCollection();

        $this->subscriber(gtmId: '  ')->onCookieGroupCollect($this->event($collection));

        $this->assertNull($collection->get(CookieProvider::SNIPPET_NAME_COOKIE_GROUP_STATISTICAL));
    }

    private function subscriber(bool $active = true, bool $externalConsent = false, string $gtmId = 'GTM-TEST'): CookieSubscriber
    {
        $config = $this->createMock(SystemConfigService::class);
        $config->method('getBool')->willReturnCallback(
            static function (string $key) use ($active, $externalConsent): bool {
                return match ($key) {
                    'NfGoogleTagManager.config.active' => $active,
                    'NfGoogleTagManager.config.externalConsent' => $externalConsent,
                    default => false,
                };
            }
        );
        $config->method('getString')->willReturn($gtmId);

        return new CookieSubscriber($config);
    }

    private function event(CookieGroupCollection $collection): CookieGroupCollectEvent
    {
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn('sales-channel-id');

        return new CookieGroupCollectEvent($collection, new Request(), $context);
    }
}
