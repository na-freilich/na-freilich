<?php declare(strict_types=1);

namespace Nf\Booking\Core\Content\Booking;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
class NfBookingCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return NfBookingEntity::class;
    }
}