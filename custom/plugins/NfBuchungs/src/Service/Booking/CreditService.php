<?php

namespace Nf\Booking\Service\Booking;

use Nf\Booking\Core\Content\Booking\BookingQuery;
use Nf\Booking\Core\Content\Booking\NfBookingEntity;
use Nf\Booking\Core\Content\BookingCredit\NfBookingCreditCollection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Nf\Booking\Core\Content\BookingCredit\NfBookingCreditEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class CreditService implements CreditServiceInterface
{
    public function __construct(
        private EntityRepository $creditRepository,
        private EntityRepository $bookingRepository,
        private BookingServiceInterface $bookingService,
        private UserServiceInterface $userService,
    ) {
    }

    public function getCustomerCredit(string $customerId, Context $context): float
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));

        /** @var NfBookingCreditCollection|null $credits */
        $credits = $this->creditRepository->search($criteria, $context)->getEntities();

        if ($credits->count() === 0) {
            return 0.0;
        }

        $availableCredits = $credits->filter(function (NfBookingCreditEntity $credit) {
            return $credit->getUsedSlots() < $credit->getTotalSlots();
        });

        $totalRemaining = 0.0;
        foreach ($availableCredits as $credit) {
            $available = $credit->getTotalSlots() - $credit->getUsedSlots();
            $totalRemaining += (float)$available;
        }

        return $totalRemaining;
    }

    private function getCreditInfo(string $customerId, $slotCount, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));

        /** @var NfBookingCreditCollection|null $credits */
        $credits = $this->creditRepository->search($criteria, $context)->getEntities();

        $appliedCredits = [];
        $remainingToCover = $slotCount;

        foreach ($credits as $credit) {
            if ($remainingToCover <= 0) break;

            $availableInCredit = $credit->getTotalSlots() - $credit->getUsedSlots();

            if ($availableInCredit <= 0) continue;

            $take = min($availableInCredit, $remainingToCover);

            $appliedCredits[] = [
                'creditId' => $credit->getId(),
                'slots' => $take
            ];

            $remainingToCover -= $take;
        }

        return $appliedCredits;
    }

    /**
     * @throws \Exception
     */
    public function applyCredit(BookingQuery $query, float $slotsCount): bool
    {

        $customerId = $query->customerId;
        $context = $query->context;
        $availableSlots = $this->getCustomerCredit($customerId, $context);

        if ($availableSlots < $slotsCount) {
            throw new \InvalidArgumentException('nfBooking.credit.notEnoughCredit');
        }

        $booking = $this->userService->getBooking($query);
        if (!$booking) {
            throw new \InvalidArgumentException('nfBooking.credit.errorInvalidBooking');
        }

        $bookingSlots = $this->userService->calculateBookingSlots($booking);
        if ($bookingSlots < $slotsCount)
            $slotsCount = $bookingSlots;

        $creditInfo = $this->getCreditInfo($customerId, $slotsCount, $context);

        $data = [
            'id' => $booking->getId(),
            'totalCreditSlots' => $slotsCount,
            'creditInfo' => $creditInfo,
        ];

        $this->bookingRepository->update([$data], $context);
        $this->bookingService->updateBookingTotalPrice($booking->getId(), $context);

        return true;
    }

    public function RemoveCredit(BookingQuery $query): bool
    {
        $context = $query->context;
        $booking = $this->userService->getBooking($query);
        if (!$booking) {
            throw new \InvalidArgumentException('nfBooking.credit.errorInvalidBooking');
        }

        if($booking->getTotalCreditSlots()){
            $data = [
                'id' => $booking->getId(),
                'totalCreditSlots' => null,
                'totalCreditAmount' => null,
                'creditInfo' => null,
            ];

            $this->bookingRepository->update([$data], $context);
            $this->bookingService->updateBookingTotalPrice($booking->getId(), $context);
        }

        return true;
    }

    public function confirmUsage(NfBookingEntity $booking, Context $context): bool
    {
        $creditInfo = $booking->getCreditInfo();

        if (empty($creditInfo) || !is_array($creditInfo)) {
            $creditInfo = $this->getCreditInfo($booking->getCustomerId(), $booking->getCreditInfo(), $context);
        }

        if (empty($creditInfo) || !is_array($creditInfo)) {
            return false;
        }

        $updateData = [];

        foreach ($creditInfo as $item) {
            $creditId = $item['creditId'] ?? null;
            $slotsToDeduct = (float)($item['slots'] ?? 0);

            if (!$creditId || $slotsToDeduct <= 0) {
                continue;
            }

            $updateData[] = [
                'id' => $creditId,
                'usedSlots' => $this->getNewUsedSlotsValue($creditId, $slotsToDeduct, $context)
            ];
        }

        if (!empty($updateData)) {
            $this->creditRepository->update($updateData, $context);
            return true;
        }

        return false;
    }
    private function getNewUsedSlotsValue(string $creditId, float $newSlots, Context $context): float
    {
        $criteria = new Criteria([$creditId]);
        /** @var NfBookingCreditEntity|null $credit */
        $credit = $this->creditRepository->search($criteria, $context)->get($creditId);

        if (!$credit) {
            return $newSlots;
        }

        return $credit->getUsedSlots() + $newSlots;
    }

}