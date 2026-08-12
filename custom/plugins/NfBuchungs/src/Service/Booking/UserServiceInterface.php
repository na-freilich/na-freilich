<?php declare(strict_types=1);

namespace Nf\Booking\Service\Booking;

use Nf\Booking\Core\Content\Booking\BookingItem\NfBookingItemCollection;
use Nf\Booking\Core\Content\Booking\NfBookingEntity;
use Nf\Booking\Core\Content\Booking\BookingQuery;

interface UserServiceInterface
{
    public function getBooking(BookingQuery $query): ?NfBookingEntity;
    public function getBookingId(BookingQuery $query): ?string;

    public function updateReservationCount($customerId, $itemCnt, $context): void;

    public function mergeItems(NfBookingItemCollection $items): ?NfBookingItemCollection;

}