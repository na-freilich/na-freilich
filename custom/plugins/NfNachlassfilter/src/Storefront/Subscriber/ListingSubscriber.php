<?php declare(strict_types=1);

namespace Nf\NachlassFilter\Storefront\Subscriber;

use Shopware\Core\Content\Cms\Events\CmsPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Product\Events\ProductListingCollectFilterEvent;
use Nf\NachlassFilter\Service\FilterService;

class ListingSubscriber implements EventSubscriberInterface {

    public function __construct(private readonly FilterService $filterService)
    {
    }

    public static function getSubscribedEvents() {
        return [
            ProductListingCollectFilterEvent::class => 'addFilter',
        ];
    }

    public function addFilter(ProductListingCollectFilterEvent $event): void
    {
        // fetch existing filters
        $filters = $event->getFilters();
        $request = $event->getRequest();

        $isNachlassFilter = $request->get('nachlass-filter');

        if (!$isNachlassFilter)
            return;

        $filter = $this->filterService->getFilter($request->get('nachlass'));

        // Add custom filter
        $filters->add($filter);
    }
}
