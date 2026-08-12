<?php

namespace Nf\Booking\Service\Booking;

use Nf\Booking\Core\Content\Booking\BookingQuery;
use Nf\Booking\Core\Content\Booking\NfBookingEntity;
use Shopware\Core\Framework\Context;

interface AdminServiceInterface
{
    public function getBooking(array $data, Context $context): ?NfBookingEntity;
}