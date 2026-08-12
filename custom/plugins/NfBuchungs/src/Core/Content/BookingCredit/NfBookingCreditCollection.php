<?php declare(strict_types=1);

namespace Nf\Booking\Core\Content\BookingCredit;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class NfBookingCreditCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return  NfBookingCreditEntity::class;
    }
}