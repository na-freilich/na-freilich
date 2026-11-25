<?php declare(strict_types=1);

namespace Nf\ProductReviewRequest\Core\Content\ProductReviewRequest;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<ProductReviewRequestEntity>
 */
class ProductReviewRequestCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductReviewRequestEntity::class;
    }
}
