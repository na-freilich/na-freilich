<?php declare(strict_types=1);

namespace Nf\Statistics\Core\Content\ProductViews;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class ProductViewsCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return StatCurrentUsersEntity::class;
    }
}