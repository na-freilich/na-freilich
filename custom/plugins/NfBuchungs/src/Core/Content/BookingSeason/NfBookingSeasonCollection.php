<?php declare(strict_types=1);

namespace Nf\Booking\Core\Content\BookingSeason;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class NfBookingSeasonCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return NfBookingSeasonEntity::class;
    }
}