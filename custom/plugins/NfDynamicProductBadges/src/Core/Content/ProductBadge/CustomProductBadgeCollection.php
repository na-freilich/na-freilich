<?php declare(strict_types=1);

namespace Nf\DynamicProductBadges\Core\Content\ProductBadge;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class CustomProductBadgeCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CustomProductBadgeEntity::class;
    }
}