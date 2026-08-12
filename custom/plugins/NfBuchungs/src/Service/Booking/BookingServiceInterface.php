<?php declare(strict_types=1);

namespace Nf\Booking\Service\Booking;

use Nf\Booking\Core\Content\Booking\BookingQuery;
use Nf\Booking\Core\Content\Booking\NfBookingEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

interface BookingServiceInterface
{
    public function reserve(BookingQuery $query): string;

    public function updateBookingTotalPrice(string $bookingId, Context $context): void;

    public function confirmReservation(string $bookingId, OrderEntity $order, Context $context): ?NfBookingEntity;

    public function confirmPaidReservation(string $bookingId, OrderEntity $order, Context $context): ?NfBookingEntity;

}