<?php declare(strict_types=1);

namespace Nf\Booking\Core\Content\BookingSeriesDiscount;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class NfBookingSeriesDiscountCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return  NfBookingSeriesDiscountEntity::class;
    }
}