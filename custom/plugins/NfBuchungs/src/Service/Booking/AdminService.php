<?php declare(strict_types=1);

namespace Nf\Booking\Service\Booking;

use Nf\Booking\Core\Content\Booking\NfBookingEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Nf\Booking\Core\Content\Booking\BookingItem\NfBookingItemEntity;
use Nf\Booking\Core\Content\Booking\BookingItem\NfBookingItemCollection;

readonly class AdminService implements AdminServiceInterface
{
    public function __construct(
        private EntityRepository $bookingRepository
    ) {
    }

    public function getBooking(array $data, Context $context): ?NfBookingEntity
    {
        $slotId = $data['slotId'] ?? null;

        $criteria = new Criteria();
        $criteria->addAssociation('customer');
        $criteria->addAssociation('items');
        $criteria->addAssociation('items.location');

        if ($slotId) {
            $criteria->addFilter(new EqualsFilter('items.id', $slotId));
        }
        else
            return null;

        $criteria->getAssociation('items')
            ->addSorting(new FieldSorting('location.name', FieldSorting::ASCENDING))
            ->addSorting(new FieldSorting('bookingDate', FieldSorting::ASCENDING))
            ->addSorting(new FieldSorting('startTime', FieldSorting::ASCENDING));
        $criteria->setLimit(1);

        /** @var NfBookingEntity|null $booking */
        $booking = $this->bookingRepository->search($criteria, $context)->first();

        if($booking)
            $this->mergeSlots($booking);

        return $booking;
    }

    private function mergeSlots(NfBookingEntity $booking): void
    {
        $items = $booking->getItems();
        if (!$items || $items->count() === 0) {
            return;
        }

        $elements = $items->getElements();
        usort($elements, static function (NfBookingItemEntity $a, NfBookingItemEntity $b) {
            return strcmp($a->getStartTime(), $b->getStartTime());
        });

        /** @var NfBookingItemEntity[] $merged */
        $merged = [];
        $current = null;

        foreach ($elements as $item) {
            if ($current === null) {
                $current = $item;
                continue;
            }

            $isSameLocation = $current->getLocationId() === $item->getLocationId();
            $isConsecutive = $current->getEndTime() === $item->getStartTime();

            if ($isSameLocation && $isConsecutive) {
                $current->setEndTime($item->getEndTime());
                $current->setUnitPrice($current->getUnitPrice() + $item->getUnitPrice());
            } else {
                $merged[] = $current;
                $current = $item;
            }
        }

        if ($current) {
            $merged[] = $current;
        }

        $newCollection = new NfBookingItemCollection($merged);
        $booking->setItems($newCollection);
    }
}