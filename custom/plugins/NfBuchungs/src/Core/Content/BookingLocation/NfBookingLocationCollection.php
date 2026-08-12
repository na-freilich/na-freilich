<?php declare(strict_types=1);

namespace Nf\Booking\Core\Content\BookingLocation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class NfBookingLocationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return NfBookingLocationEntity::class;
    }
}
