<?php declare(strict_types=1);

namespace Nf\Booking\Service\Booking;

use Nf\Booking\Core\Content\Booking\BookingQuery;
use Nf\Booking\Core\Content\Booking\NfBookingEntity;
use Nf\Booking\Core\Content\Booking\BookingItem\NfBookingItemCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Checkout\Cart\AbstractCartPersister;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SystemConfig\SystemConfigService;

readonly class UserService implements UserServiceInterface
{
    public const CUSTOM_FIELD_TOTAL_SLOTS = 'nf_booking_slots_total';
    public const CUSTOM_FIELD_PAID_SLOTS = 'nf_booking_paid_slots';
    public const CUSTOM_FIELD_PAID_FREE_SLOTS = 'nf_booking_paid_free_slots';

    public function __construct(
        private EntityRepository      $bookingRepository,
        private EntityRepository      $customerRepository,
        private EntityRepository      $creditRepository,
        private AbstractCartPersister $cartPersister,
        private SystemConfigService   $systemConfigService,
    ) {
    }

    public function getBooking(BookingQuery $query): ?NfBookingEntity
    {
        $customerId = $query->customerId;
        $token = $query->token;
        $productId =  $query->productId;
        $context = $query->context;

        $criteria = new Criteria();
        if (!$query->adminId)
            $criteria->addFilter(new EqualsFilter('status', 'pending'));

        if($productId)
            $criteria->addFilter(new EqualsFilter('productId', $productId));
        $criteria->addAssociation('product');
        $criteria->addAssociation('discount');
        $criteria->addAssociation('items.location');
        $criteria->getAssociation('items')
            ->addSorting(new FieldSorting('location.name', FieldSorting::ASCENDING))
            ->addSorting(new FieldSorting('bookingDate', FieldSorting::ASCENDING))
            ->addSorting(new FieldSorting('startTime', FieldSorting::ASCENDING));
        $criteria->setLimit(1);

        if ($customerId) {
            $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        } else {
            $criteria->addFilter(new EqualsFilter('cartToken', $token));
        }
        /** @var NfBookingEntity|null $booking */
        $booking = $this->bookingRepository->search($criteria, $context)->first();

        return $booking;
    }

    public function mergeItems(NfBookingItemCollection $items): ?NfBookingItemCollection
    {
        $mergedItems = [];
        $currentGroup = null;

        foreach ($items as $item) {
            if ($currentGroup === null) {
                $currentGroup = $item;
                continue;
            }

            if (($currentGroup->getLocationId() == $item->getLocationId()) &&
                ($currentGroup->getBookingDate() == $item->getBookingDate()) &&
                ($currentGroup->getEndTime() === $item->getStartTime()))
            {
                $currentGroup->setEndTime($item->getEndTime());
                $currentGroup->setUnitPrice($currentGroup->getUnitPrice() + $item->getUnitPrice());
                $currentGroup->setId($currentGroup->getId().'|'.$item->getId());

            } else {
                $mergedItems[] = $currentGroup;
                $currentGroup = $item;
            }
        }

        if ($currentGroup !== null) {
            $mergedItems[] = $currentGroup;
        }

       return new NfBookingItemCollection($mergedItems);
    }

    public function getBookingCart(BookingQuery $query): ?NfBookingEntity
    {
        $booking = $this->getBooking($query);

        if (!$booking) return null;

        $items = $this->mergeItems($booking->getItems());
        $booking->setItems($items);

        $this->validate($booking);

        return $booking;
    }

    public function getBookingId(BookingQuery $query): ?string
    {
        return $this->getBooking($query)?->getId();
    }

    public function calculateBookingSlots(NfBookingEntity $booking): float
    {
        $totalSeconds = 0;
        $items = $booking->getItems();

        if (!$items) {
            return 0.0;
        }

        foreach ($items as $item) {
            $start = $item->getStartTime();
            $end = $item->getEndTime();

            if ($start instanceof \DateTimeInterface && $end instanceof \DateTimeInterface) {
                $totalSeconds += ($end->getTimestamp() - $start->getTimestamp());
            } else {
                $totalSeconds += (strtotime((string)$end) - strtotime((string)$start));
            }
        }

        return $totalSeconds / 3600;
    }

    public function updateUser(string $oldToken, ?string $newToken, ?string $customerId, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('cartToken', $oldToken));
        $bookingIds = $this->bookingRepository->searchIds($criteria, $context);

        if ($bookingIds->getTotal() === 0) {
            return;
        }

        $updateData = [];
        foreach ($bookingIds->getIds() as $id) {
            $row = ['id' => $id];

            if ($newToken)
                $row['cartToken'] = $newToken;

            if ($customerId)
                $row['customerId'] = $customerId;

            if (count($row) > 1) {
                $updateData[] = $row;
            }
        }

        if (!empty($updateData)) {
            $this->bookingRepository->update($updateData, $context);
        }
    }

    public function checkExpired(SalesChannelContext $context): void
    {
        $now = (new \DateTime())->format('Y-m-d H:i:s');

        $criteria = new Criteria();
        $criteria->addFilter(new RangeFilter('expiresAt', [
            RangeFilter::LT => $now
        ]));

        $criteria->addFilter(new EqualsFilter('status', 'pending'));

        $expiredBookings = $this->bookingRepository->search($criteria, $context->getContext());

        if ($expiredBookings->getTotal() > 0) {
            $idsToDelete = [];
            foreach ($expiredBookings->getIds() as $id) {
                $idsToDelete[] = ['id' => $id];
            }
            $this->bookingRepository->delete($idsToDelete, $context->getContext());
        }
    }

    public function deleteFromCart(array $bookingIds, Context $context)
    {
        $token = $context->getToken();
        try {
            $cart = $this->cartPersister->load($token, $context);

            foreach ($cart->getLineItems() as $lineItem) {
                $bookingId = $lineItem->getPayloadValue('nfBookingId');
                if (!$bookingId) {
                    continue;
                }

                if (in_array($bookingId, $bookingIds) ) {
                    $cart->remove($lineItem->getId());
                }
            }

            if ($cart->getLineItems()->count() > 0) {
                $this->cartPersister->save($cart, $context);
            } else {
                $this->cartPersister->delete($token, $context);
            }
        } catch (\Exception $e) {
        }
    }

    private function validate(NfBookingEntity $booking): void
    {
        if($booking->getProduct())
        {
            $customFields = $booking->getProduct()->getCustomFields() ?? [];
            $minDuration = (int)($customFields['min_booking_duration'] ?? 0);
            $hasShortSlots = false;

            foreach ($booking->getItems() as $item) {
                $startTime = new \DateTime($item->getStartTime());
                $endTime = new \DateTime($item->getEndTime());
                $interval = $startTime->diff($endTime);

                $durationInMinutes = ($interval->h * 60) + $interval->i;
                $isTooShort = ($durationInMinutes < $minDuration);
                if ($isTooShort) {
                    $hasShortSlots = true;
                }

                $item->addExtension('validation', new ArrayStruct([
                    'isTooShort' => $isTooShort,
                    'duration' => $durationInMinutes,
                    'required' => $minDuration
                ]));
            }

            $booking->addExtension('validation', new ArrayStruct([
                'hasError' => $hasShortSlots,
                'minRequired' => $minDuration
            ]));
        }
    }

    public function updateReservationCount($customerId, $itemCnt, $context): void
    {
        $criteria = new Criteria([$customerId]);
        $customer = $this->customerRepository->search($criteria, $context)->get($customerId);

        if (!$customer) {
            return;
        }

        $customFields = $customer->getCustomFields() ?? [];

        $currentTotal = ($customFields[self::CUSTOM_FIELD_TOTAL_SLOTS] ?? 0) + $itemCnt;

        $this->customerRepository->update([
            [
                'id' => $customerId,
                'customFields' => array_merge($customFields, [
                    self::CUSTOM_FIELD_TOTAL_SLOTS => $currentTotal,
                ]),
            ]
        ], $context);
    }

    public function updatePaidReservationCount($customerId, $itemCnt, $context): void
    {
        $criteria = new Criteria([$customerId]);
        $customer = $this->customerRepository->search($criteria, $context)->get($customerId);

        if (!$customer) {
            return;
        }

        $customFields = $customer->getCustomFields() ?? [];

        $paidTotal = ($customFields[self::CUSTOM_FIELD_PAID_SLOTS] ?? 0) + $itemCnt;
        $paidFree = ($customFields[self::CUSTOM_FIELD_PAID_FREE_SLOTS] ?? 0) + $itemCnt;

        $threshold = $this->systemConfigService->getInt('NfBooking.config.loyaltyThreshold') ?: 10;

        if ($threshold > 0 && $paidFree >= $threshold)
        {
            $paidFree -= $threshold;
            $this->creditRepository->create([
                [
                    'id' => Uuid::randomHex(),
                    'customerId' => $customerId,
                    'totalSlots' => 1,
                    'usedSlots' => 0,
                    'active' => true,
                ]
            ], $context);
        }

        $this->customerRepository->update([
            [
                'id' => $customerId,
                'customFields' => array_merge($customFields, [
                    self::CUSTOM_FIELD_PAID_SLOTS => $paidTotal,
                    self::CUSTOM_FIELD_PAID_FREE_SLOTS => $paidFree,
                ]),
            ]
        ], $context);
    }
}