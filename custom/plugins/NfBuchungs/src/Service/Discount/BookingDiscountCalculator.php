<?php declare(strict_types=1);

namespace Nf\Booking\Service\Discount;

use Nf\Booking\Core\Content\BookingSeriesDiscount\NfBookingSeriesDiscountEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class BookingDiscountCalculator implements BookingDiscountCalculatorInterface
{
    private EntityRepository $discountRepository;

    public function __construct(EntityRepository $discountRepository)
    {
        $this->discountRepository = $discountRepository;
    }

    public function getDiscount(int $bookingCount, Context $context): ?NfBookingSeriesDiscountEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new RangeFilter('minCount', [
            RangeFilter::LTE => $bookingCount
        ]));

        $criteria->addSorting(new FieldSorting('minCount', FieldSorting::DESCENDING));
        $criteria->setLimit(1);

        /** @var NfBookingSeriesDiscountEntity|null $discount */
        $discount = $this->discountRepository->search($criteria, $context)->first();

        return $discount ?: null;
    }

//    public function calculateDiscountedPrice(float $totalPrice, int $bookingCount, Context $context): float
//    {
//        $percentage = $this->getDiscountPercentage($bookingCount, $context);
//
//        if ($percentage <= 0) {
//            return $totalPrice;
//        }
//
//        return $totalPrice * (1 - ($percentage / 100));
//    }
}