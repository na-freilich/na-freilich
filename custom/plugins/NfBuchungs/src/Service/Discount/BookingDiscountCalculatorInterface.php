<?php declare(strict_types=1);

namespace Nf\Booking\Service\Discount;

use Shopware\Core\Framework\Context;
use Nf\Booking\Core\Content\BookingSeriesDiscount\NfBookingSeriesDiscountEntity;
interface BookingDiscountCalculatorInterface
{
    public function getDiscount(int $bookingCount, Context $context): ?NfBookingSeriesDiscountEntity;

//    public function calculateDiscountedPrice(float $totalPrice, int $bookingCount, Context $context): float;
}