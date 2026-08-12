<?php

namespace Nf\Booking\Core\Content\BookingLocation\Aggregate;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class NfBookingLocationTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return NfBookingLocationTranslationEntity::class;
    }
}