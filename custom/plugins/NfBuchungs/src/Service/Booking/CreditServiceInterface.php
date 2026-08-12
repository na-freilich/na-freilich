<?php declare(strict_types=1);

namespace Nf\Booking\Service\Booking;

use Nf\Booking\Core\Content\Booking\NfBookingEntity;
use Nf\Booking\Core\Content\Booking\BookingQuery;
use Shopware\Core\Framework\Context;

interface CreditServiceInterface
{
    public function applyCredit(BookingQuery $query, float $slotsCount): bool;

    public function RemoveCredit(BookingQuery $query): bool;

    public function confirmUsage(NfBookingEntity $booking, Context $context): bool;
    public function getCustomerCredit(string $customerId, Context $context): float;
}