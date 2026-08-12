<?php declare(strict_types=1);

namespace Nf\Booking\Core\Content\Booking\BookingItem;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class NfBookingItemCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return NfBookingItemEntity::class;
    }
}