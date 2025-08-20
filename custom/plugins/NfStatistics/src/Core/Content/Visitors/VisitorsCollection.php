<?php declare(strict_types=1);

namespace Nf\Statistics\Core\Content\Visitors;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class VisitorsCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return VisitorsEntity::class;
    }
}