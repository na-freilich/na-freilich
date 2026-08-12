<?php declare(strict_types=1);

namespace Nf\Booking\Service\Booking;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Nf\Booking\Core\Content\BookingSeason\NfBookingSeasonEntity;
use Nf\Booking\Core\Content\Booking\BookingItem\NfBookingItemCollection;
use Nf\Booking\Core\Content\Booking\BookingQuery;

class BookingPriceService implements BookingPriceServiceInterface
{

    private ?ProductEntity $product = null;

    public function __construct(
        private EntityRepository     $seasonRepository,
        private EntityRepository     $bookingItemRepository,
        private EntityRepository     $productRepository,
        private UserServiceInterface $userService
    ) {
    }

    private function getProduct(string $productId, Context $context): ?ProductEntity
    {
        if ($this->product)
            return $this->product;

        if(!$productId)
            return null;

        $criteria = new Criteria([$productId]);
        $this->product = $this->productRepository->search($criteria, $context)->first();

        return $this->product;
    }

    public function getSlots(BookingQuery $query): ?array
    {
        $season = $this->getSeason($query->date, $query->context);

        $slots = $this->getDateSlots($query->productId,
            $season,
            $query->date,
            $query->context);

        if (!$slots)
            return [];
//dd();
        $slots = $this->appendStatusToSlots($slots, $query);
        if (!$query->adminId)
            $slots = $this->appendMinimalGroupToSlots($slots, $query);

        return $slots;
    }

//    public function getSlotPrice(
    public function getSlotPriceDetails(
        BookingQuery $query,
        int $offset = 0,
        float $creditFreeSlots = 0.0
    ): array {
        $totalPrice = 0.0;
        $totalDiscount = 0.0;
        $currentIntervalCounter = 0;
        $foundAtLeastOne = false;

        $date = $query->date;
        $productId = $query->productId;
        $context = $query->context;
        $season = $this->getSeason($date, $context);
        $slots = $this->getDateSlots($productId, $season, $date, $context);
        $requestedStart = substr($query->timeStart, 0, 5);
        $requestedEnd = substr($query->timeEnd, 0, 5);

        foreach ($slots as $slot) {
            $slotStart = substr($slot['start'], 0, 5);

            if ($slotStart >= $requestedStart && $slotStart < $requestedEnd) {

                $currentIntervalCounter++;
                $globalSlotPosition = $offset + $currentIntervalCounter;

                $basePrice = ($globalSlotPosition >= 3)
                    ? (float)($slot['priceSubsequent'] ?? $slot['price'])
                    : (float)$slot['price'];

                if ($creditFreeSlots >= 0.5) {
                    $totalDiscount += $basePrice;
                    $creditFreeSlots -= 0.5;
                } else {
                    $totalPrice += $basePrice;
                }

                $foundAtLeastOne = true;
            }
        }

        if ($foundAtLeastOne) {
            return [
                'totalPrice' => $totalPrice,
                'discountAmount' => $totalDiscount,
                'slotsProcessed' => $currentIntervalCounter,
                'creditFreeSlots' => $creditFreeSlots
            ];
        }

       throw new \InvalidArgumentException(
            sprintf('The selected time slot "%s" is not available for the date %s.', $requestedStart, $date)
        );
    }

    private function getDateSlots(
        null|string $productId,
        NfBookingSeasonEntity $season,
        string $date,
        Context $context): ?array
    {
        $bookingStep = 30;

        if ($productId)
            $product = $this->getProduct($productId, $context);
        else
            $product = null;

        if ($product) {
            $customFields = $product->getCustomFields();
            $bookingStep = (int)$customFields['booking_step'] ?? 30;
        }

        $dateTime = new \DateTimeImmutable($date);

        $dayOfWeek = (int) $dateTime->format('N');

        $priceRules = $season->get('priceRules');
        if (!$priceRules || $priceRules->count() === 0) {
            return null;
        }

        $allSlots = [];
        foreach ($priceRules as $rule) {
            $allowedDays = $rule->get('days');
            if (is_array($allowedDays) && in_array($dayOfWeek, $allowedDays)) {
                $start = new \DateTime($rule->get('startTime'));
                $end = new \DateTime($rule->get('endTime'));
                $price = $rule->get('price');

                while ($start < $end) {
                    $slotStart = $start->format('H:i');

                    $start->modify("+$bookingStep minutes");

                    if ($start > $end) break;

                    $allSlots[] = [
                        'start' => $slotStart,
                        'end'   => $start->format('H:i'),
                        'price' => $price / (60 / $bookingStep),
                        'priceHour' => $price,
                        'priceSubsequent' => $rule->get('priceSubsequent'),
                        'ruleId'=> $rule->get('id')
                    ];
                }
            }
        }

        if (empty($allSlots)) {
            return null;
        }

        usort($allSlots, function ($a, $b) {
            return strcmp($a['start'], $b['start']);
        });

        return $allSlots;
    }
    private function getSeason(string $date, Context $context): ?NfBookingSeasonEntity
    {
        $dateTime = new \DateTimeImmutable($date);
        $criteria = new Criteria();
        $criteria->addAssociation('priceRules');

        $seasons = $this->seasonRepository->search($criteria, $context);
        $mmdd = (int)$dateTime->format('md');

        foreach ($seasons as $season) {
            $start = (int)$season->getStartDate();
            $end = (int)$season->getEndDate();

            if ($start <= $end) {
                if ($mmdd >= $start && $mmdd <= $end) return $season;
            } else {
                if ($mmdd >= $start || $mmdd <= $end) return $season;
            }
        }
    }

    private function appendStatusToSlots(
        array $slots,
        BookingQuery $query): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('bookingDate', $query->date));
        $criteria->addFilter(new EqualsFilter('locationId', $query->locationId));
        $criteria->addAssociation('booking');

        /** @var NfBookingItemCollection $bookedItems */
        $bookedItems = $this->bookingItemRepository->search($criteria, $query->context)->getEntities();
        $currentBookingId = $this->userService->getBookingId($query);

        foreach ($slots as $key => $slot) {
            $slots[$key]['status'] = 'available';
            $slots[$key]['isAvailable'] = true;

            foreach ($bookedItems as $item) {
                $itemStart = $item->getStartTime() instanceof \DateTimeInterface
                    ? $item->getStartTime()->format('H:i')
                    : substr((string)$item->getStartTime(), 0, 5);

                $itemEnd = $item->getEndTime() instanceof \DateTimeInterface
                    ? $item->getEndTime()->format('H:i')
                    : substr((string)$item->getEndTime(), 0, 5);
                if ($slot['start'] < $itemEnd && $slot['end'] > $itemStart)
                {
                    if($query->adminId){
                        $slots[$key]['slotId'] = $item->getId();
                        $slots[$key]['bookingId'] = $item->getBookingId();
                        $currentBookingId = null;
                     }

                    if($item->getBooking()->getId() == $currentBookingId)
                    {
                        $slots[$key]['status'] = 'selected';
                        $slots[$key]['id'] = $item->getId();
                    }
                    else
                    {
                        $slots[$key]['status'] = $item->getBooking() ? $item->getBooking()->getStatus() : 'booked';
                        $slots[$key]['isAvailable'] = false;
                    }

                    break;
                }
            }
        }

        return $slots;
    }

    private function appendMinimalGroupToSlots(
        array $slots,
        BookingQuery $query): array
    {
        $bookingStep = 30;
        $minDuration = 60;

        $product = $this->getProduct($query->productId, $query->context);
        if ($product) {
            $customFields = $product->getCustomFields();
            $bookingStep = (int)$customFields['booking_step'] ?? $bookingStep;
            $minDuration = $customFields['min_booking_duration'] ?? $minDuration;
        }
        if ($bookingStep == $minDuration)
            return $slots;

        $indexedSlots = [];
        $i = 1;
        foreach ($slots as $time => $slot) {
            $slot['groupId'] = $i;
            $slot['group'] = [];
            $indexedSlots[] = [
                'time' => $time,
                'data' => $slot
            ];
            $i++;
        }

        $totalSlots = count($indexedSlots);
        $neededSlotsCount = (int)($minDuration / $bookingStep);

        foreach ($indexedSlots as $index => &$currentSlot) {
            $currentSlot['data']['groupPrice'] = $currentSlot['data']['priceSubsequent'];

            if ($currentSlot['data']['status'] != 'available') {
                continue;
            }

            $isAdjacentToSelected = false;
            if (($index > 0 && $indexedSlots[$index - 1]['data']['status'] === 'selected') ||
                ($index < $totalSlots - 1 && $indexedSlots[$index + 1]['data']['status'] === 'selected')) {
                $isAdjacentToSelected = true;
            }

            if ($isAdjacentToSelected) {
                $currentSlot['data']['group'] = [$currentSlot['data']['groupId']];
                continue;
            }

            $validGroup = [];

            for ($offset = 0; $offset > -$neededSlotsCount; $offset--) {
                $potentialGroup = [];
                $groupPrice = 0;
                $isPossible = true;
                $startIndex = $index + $offset;

                for ($j = 0; $j < $neededSlotsCount; $j++) {
                    $checkIndex = $startIndex + $j;

                    if ($checkIndex < 0 || $checkIndex >= $totalSlots || $indexedSlots[$checkIndex]['data']['status'] != "available") {
                        $isPossible = false;
                        break;
                    }

                    $slotData = $indexedSlots[$checkIndex]['data'];
                    $potentialGroup[] = $slotData['groupId'];

                    if ($j <= 1) {
                        $groupPrice += (float)$slotData['price'];
                    } else {
                        $groupPrice += (float)($slotData['priceSubsequent'] ?? $slotData['price']);
                    }
                }

                if ($isPossible) {
                    $validGroup = $potentialGroup;
                    $currentSlot['data']['groupPrice'] = $groupPrice;
                    break;
                }
            }

            if (!empty($validGroup)) {
                $currentSlot['data']['group'] = $validGroup;
            } else {
                $currentSlot['data']['isAvailable'] = false;
                $currentSlot['data']['status'] = 'disabled';
            }
        }
        unset($currentSlot);


        // canBeRemovedAlone
        $selectedSequences = [];
        $currentSequence = [];

        foreach ($indexedSlots as $index => $slot) {
            if ($slot['data']['status'] === 'selected') {
                $currentSequence[] = $index;
            } else {
                if (count($currentSequence) > 0) {
                    $selectedSequences[] = $currentSequence;
                }
                $currentSequence = [];
            }
        }
        if (count($currentSequence) > 0) $selectedSequences[] = $currentSequence;

        foreach ($selectedSequences as $sequence) {
            $count = count($sequence);

            if ($count > $neededSlotsCount) {
                $firstIndex = $sequence[0];
                $lastIndex = $sequence[$count - 1];

                $indexedSlots[$firstIndex]['data']['canBeRemovedAlone'] = true;
                $indexedSlots[$lastIndex]['data']['canBeRemovedAlone'] = true;
            }
        }

        $finalSlots = [];
        foreach ($indexedSlots as $item) {
            $finalSlots[$item['time']] = $item['data'];
        }

        return $finalSlots;
    }
}