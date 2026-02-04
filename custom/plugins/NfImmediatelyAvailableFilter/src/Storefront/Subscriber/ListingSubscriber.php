<?php declare(strict_types=1);

namespace Nf\ImmediatelyAvailableFilter\Storefront\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Product\Events\ProductListingCollectFilterEvent;
use Nf\ImmediatelyAvailableFilter\Service\FilterService;

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

        $isImmediatelyAvailableFilter = $request->get('immediately-available-filter');

        if (!$isImmediatelyAvailableFilter)
            return;

        $filter = $this->filterService->getFilter($request->get('immediately-available'));

        // Add custom filter
        $filters->add($filter);
    }
}
