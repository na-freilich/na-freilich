<?php declare(strict_types=1);

namespace Nf\ProductReviewRequest\Core\Flow\Dispatching\Storer;

use Nf\ProductReviewRequest\Core\Flow\ProductReviewDueEvent;
use Shopware\Core\Content\Flow\Dispatching\StorableFlow;
use Shopware\Core\Content\Flow\Dispatching\Storer\FlowStorer;
use Shopware\Core\Framework\Event\FlowEventAware;

final class ProductReviewLinkStorer extends FlowStorer
{
    public function store(FlowEventAware $event, array $stored): array
    {
        if (!$event instanceof ProductReviewDueEvent || isset($stored[ProductReviewDueEvent::PRODUCT_REVIEW_LINKS])) {
            return $stored;
        }

        $stored[ProductReviewDueEvent::PRODUCT_REVIEW_LINKS] = $event->getProductReviewLinks();

        return $stored;
    }

    public function restore(StorableFlow $storable): void
    {
        if (!$storable->hasStore(ProductReviewDueEvent::PRODUCT_REVIEW_LINKS)) {
            return;
        }

        $storable->setData(
            ProductReviewDueEvent::PRODUCT_REVIEW_LINKS,
            $storable->getStore(ProductReviewDueEvent::PRODUCT_REVIEW_LINKS),
        );
    }
}
