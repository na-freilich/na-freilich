<?php declare(strict_types=1);

namespace Nf\Statistics\Core\Content\StatCurrentUsers;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class StatCurrentUsersCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return StatCurrentUsersEntity::class;
    }
}