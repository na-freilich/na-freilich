<?php declare(strict_types=1);

namespace Nf\OrderDay\Core\Content\OrderDay;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(OrderDayEntity $entity)
 * @method void set(string $key, OrderDayEntity $entity)
 * @method OrderDayEntity[] getIterator()
 * @method OrderDayEntity[] getElements()
 * @method OrderDayEntity|null get(string $key)
 * @method OrderDayEntity|null first()
 * @method OrderDayEntity|null last()
 */
class OrderDayCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return OrderDayEntity::class;
    }
}
