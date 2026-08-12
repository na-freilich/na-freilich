<?php declare(strict_types=1);

namespace Nf\Booking\Service\Booking;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Context;
use Nf\Booking\Core\Content\Booking\BookingItem\NfBookingItemCollection;
use Nf\Booking\Core\Content\Booking\NfBookingEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Nf\Booking\Core\Content\Booking\BookingQuery;
use Nf\Booking\Service\Discount\BookingDiscountCalculatorInterface;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;

readonly class BookingService implements BookingServiceInterface
{
    public function __construct(
        private EntityRepository             $bookingRepository,
        private EntityRepository             $bookingItemRepository,
        private BookingPriceServiceInterface $priceService,
        private UserServiceInterface         $userService,
        private SystemConfigService          $systemConfigService,
        private BookingDiscountCalculatorInterface $discountCalculator
    ) {}

    /**
     * @throws \DateMalformedStringException
     * @throws \Exception
     */
    public function deleteSlot(BookingQuery $query, string $slotId): string
    {
        $context = $query->context;

        $booking = $this->userService->getBooking($query);
        if(!$booking)
            return '';

        $item = $booking->getItems()->get($slotId);
        if ($item) {
            $startTimeInDb = substr($item->getStartTime(), 0, 5);
            $endTimeInDb   = substr($item->getEndTime(), 0, 5);

            if ($startTimeInDb != $query->timeStart) {
                $this->bookingItemRepository->update([[
                    'id' => $item->getId(),
                    'endTime'   => $query->timeEnd
                ]], $context);
            }
            elseif ($endTimeInDb != $query->timeEnd) {
                $this->bookingItemRepository->update([[
                    'id' => $item->getId(),
                    'startTime' => $query->timeEnd
                ]], $context);
            }
            else
            {
                $this->bookingItemRepository->delete([
                    ['id' => $item->getId()]
                ], $context);
            }

            $this->updateBookingTotalPrice($booking->getId(), $context);
        }

        return $booking->getId();
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \Exception
     */
    public function reserve(BookingQuery $query): string
    {
        $customerId = $query->customerId;
        $token = $query->token;
        $context = $query->context;

        $booking = $this->userService->getBooking($query);

        if (isset($data['deleteItem']) && $data['deleteItem'] === '1') {
            if (!$booking)
                return '';

            $change = false;
            foreach ($booking->get('items') as $item) {
                $startTime = $item->getStartTime();
                if (is_string($startTime)) {
                    $startTime = new \DateTime($startTime);
                }
                $formattedItemStart = $startTime->format('H:i');

                $bookingDate = $item->getBookingDate();
                if (is_string($bookingDate)) {
                    $bookingDate = new \DateTime($bookingDate);
                }
                $formattedItemDate = $bookingDate->format('Y-m-d');

                if ($formattedItemStart === substr($data['timeStart'], 0, 5) &&
                    $formattedItemDate === $data['date']
                ) {
                    $change = true;
                    $this->bookingItemRepository->delete([['id' => $item->getId()]], $context);
                }
            }

            if ($change) {
                $this->updateBookingTotalPrice($booking->getId(), $context);
            }

            return '';
        }

        $this->validateSlotAvailability($query);

        $result = $this->priceService->getSlotPriceDetails($query);
        $price = $result['totalPrice'];

        if(!$booking)
        {
            $bookingId = Uuid::randomHex();
            $timeout = $this->systemConfigService->getInt('NfBooking.config.bookingTimeout') ?: 30;
            $now = new \DateTimeImmutable();
            $expiresAt = $now->modify(sprintf('+%d minutes', $timeout));

            $data = [
                'id' => $bookingId,
                'customerId' => $customerId ?: null,
                'productId' => $query->productId,
                'cartToken' => $token,
                'expiresAt' => $expiresAt,
                'orderNumber' => 'RES-' . strtoupper(substr(Uuid::randomHex(), 0, 6)),
                'totalPrice' => (float)$price,
                'status' => 'pending',
                'items' => [
                    [
                        'locationId' => $query->locationId,
                        'bookingDate' => $query->date,
                        'startTime' => $query->timeStart,
                        'endTime' => $query->timeEnd,
                        'unitPrice' => (float)$price,
                    ]
                ]
            ];

            if ($query->adminId)
            {
                $data['productId'] = null;
                $data['status'] = 'blocked';
            }

            $this->bookingRepository->create([
                $data
            ], $context);
        } else
        {
            $bookingId = $booking->getId();
            $this->bookingRepository->upsert([
                [
                    'id' => $bookingId,
                    'items' => [
                        [
                            'locationId' => $query->locationId,
                            'bookingDate' => $query->date,
                            'startTime' => $query->timeStart,
                            'endTime' => $query->timeEnd,
                            'unitPrice' => (float)$price,
                        ]
                    ]
                ]
            ], $context);

            $this->updateBookingTotalPrice($bookingId, $context);
        }

        return $bookingId;
    }

    public function deleteItem(
        BookingQuery $query,
        string $itemId): ?bool
    {
        $context = $query->context;
        $booking = $this->userService->getBooking($query);

        if (!$booking)
            return false;

        $idsToDelete = explode('|', $itemId);
        $deletePayload = [];

        foreach ($booking->get('items') as $item) {
            if (in_array($item->getId(), $idsToDelete))
                $deletePayload[] = ['id' => trim($item->getId())];
        }

        $this->bookingItemRepository->delete(
            $deletePayload,
            $context
        );

        $this->updateBookingTotalPrice($booking->getId(), $context);

        return true;
    }

    private function validateSlotAvailability(BookingQuery $query): void
    {
        $criteria = new Criteria();
        $context = $query->context;

        $criteria->addFilter(new EqualsFilter('bookingDate', $query->date));
        $criteria->addFilter(new EqualsFilter('locationId', $query->locationId));

        $criteria->addAssociation('booking');

        $itemStart = substr($query->timeStart, 0, 5);
        $itemEnd = substr($query->timeEnd, 0, 5);

        /** @var NfBookingItemCollection $existingItems */
        $existingItems = $this->bookingItemRepository->search($criteria, $context)->getEntities();

        foreach ($existingItems as $item) {
            $existingStart = $item->getStartTime() instanceof \DateTimeInterface
                ? $item->getStartTime()->format('H:i')
                : substr((string)$item->getStartTime(), 0, 5);

            $existingEnd = $item->getEndTime() instanceof \DateTimeInterface
                ? $item->getEndTime()->format('H:i')
                : substr((string)$item->getEndTime(), 0, 5);

            if ($itemStart < $existingEnd && $itemEnd > $existingStart) {
                $booking = $item->getBooking();

                if ($booking && in_array($booking->getStatus(), ['pending', 'ordered', 'paid', 'completed'])) {
                        throw new \Exception('This time slot is already booked.');
                }
            }
        }
    }

    private function getReservationCount($items) :int
    {
        $itemCnt = 0;
        $lastEntTime = null;
        $lastDate = null;
        $lastLocationId = null;

        foreach ($items as $item) {
            if (
                $lastEntTime != $item->get('startTime') or
                $lastDate != $item->get('bookingDate') or
                $lastLocationId != $item->get('locationId')
            ){
                $itemCnt++;
            }

            $lastEntTime = $item->get('endTime');
            $lastDate = $item->get('bookingDate');
            $lastLocationId = $item->get('locationId');
        }
        return $itemCnt;
    }

    public function updateBookingTotalPrice(string $bookingId, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('bookingId', $bookingId));
        $criteria->addAssociation('booking');
        $criteria->addSorting(new FieldSorting('locationId', FieldSorting::ASCENDING))
            ->addSorting(new FieldSorting('bookingDate', FieldSorting::ASCENDING))
            ->addSorting(new FieldSorting('startTime', FieldSorting::ASCENDING));

        $items = $this->bookingItemRepository->search($criteria, $context)->getEntities();
        $freeSlots = $items->first()?->getBooking()?->getTotalCreditSlots() ?? 0.0;

        if ($items->count() === 0) {
            $this->bookingRepository->delete([['id' => $bookingId]], $context);
            return;
        }

        $total = 0;
        $offset = 0;
        $itemCnt = $this->getReservationCount($items);
        $creditDiscountAmount = 0;
        $lastEntTime = null;
        $lastDate = null;
        $lastLocationId = null;
        foreach ($items as $item) {
            if (
                $lastEntTime != $item->get('startTime') or
                $lastDate != $item->get('bookingDate') or
                $lastLocationId != $item->get('locationId')
            ){
                $offset = 0;
            }

            $query = new BookingQuery(
                context: $context,
                date: $item->get('bookingDate')->format('Y-m-d'),
                timeStart: $item->get('startTime'),
                timeEnd: $item->get('endTime'),
                productId: $item->get('booking')->getProductId(),
            );

            $result = $this->priceService->getSlotPriceDetails($query, $offset, $freeSlots);
            $price = $result['totalPrice'];
            $freeSlots = $result['creditFreeSlots'];
            $creditDiscountAmount += $result['discountAmount'];

            $start = new \DateTime($query->timeStart);
            $end = new \DateTime($query->timeEnd);
            $diffInMinutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;
            $offset = $offset + (int)($diffInMinutes / 30);
            $lastEntTime = $item->get('endTime');
            $lastDate = $item->get('bookingDate');
            $lastLocationId = $item->get('locationId');

            if($price != $item->getUnitPrice())
            {
                $this->bookingItemRepository->update([
                    [
                        'id' => $item->getId(),
                        'unitPrice' => $price
                    ]
                ], $context);
            }
            $total += $price;
        }

        $data = [
            'id' => $bookingId,
            'originalPrice' => $total,
            'totalPrice' => $total,
            'discountId' => null,
            'discountAmount' => 0,
            'totalCreditAmount' => $creditDiscountAmount,
        ];

        $discount = null;
        if ($context->getSource() instanceof SalesChannelApiSource)
            $discount = $this->discountCalculator->getDiscount($itemCnt, $context);

        if($discount)
        {
            $discountPrc = $discount->getDiscountPercentage();
            $discountAmount =  $total * ($discountPrc / 100);
            $data['discountId'] = $discount->getId();
            $data['discountAmount'] = $discountAmount;
            $data['totalPrice'] = $total - $discountAmount;
        }

        $this->bookingRepository->update([$data], $context);
    }

    public function getBookingPrice(string $bookingId, SalesChannelContext $context): float
    {
        $criteria = new Criteria([$bookingId]);

        /** @var NfBookingEntity|null $booking */
        $booking = $this->bookingRepository->search($criteria, $context->getContext())->first();

        if(!$booking)
            return -1;

        return $booking->getTotalPrice();
    }

    /**
     * @throws \Exception
     */
    public function confirmReservation(string $bookingId, OrderEntity $order, Context $context): ?NfBookingEntity
    {
        $actualCustomerId = $order->getOrderCustomer()->getCustomerId();

        $booking = $this->getBooking($bookingId, $context);

        $date = [
            'id' => $bookingId,
            'orderId' => $order->getId(),
            'status' => 'ordered',
            'customerId' => $actualCustomerId,
        ];

        $this->bookingRepository->update([
            $date
        ], $context);

        $itemCnt = $this->getReservationCount($booking->getItems());
        $this->userService->updateReservationCount($actualCustomerId, $itemCnt, $context);

        return $booking;
    }

    private function getBooking(string $bookingId,  Context $context): NfBookingEntity
    {
        $criteria = new Criteria([$bookingId]);
        $criteria->addAssociation('items');
        $criteria->getAssociation('items')
            ->addSorting(new FieldSorting('location.name', FieldSorting::ASCENDING))
            ->addSorting(new FieldSorting('bookingDate', FieldSorting::ASCENDING))
            ->addSorting(new FieldSorting('startTime', FieldSorting::ASCENDING));
        $criteria->setLimit(1);

        /** @var NfBookingEntity|null $booking */
        $booking = $this->bookingRepository->search($criteria, $context)->first();

        if (!$booking) {
            throw new \Exception("Booking not found");
        }

        return $booking;
    }

    /**
     * @throws \Exception
     */
    public function confirmPaidReservation(string $bookingId, OrderEntity $order, Context $context): ?NfBookingEntity
    {
        $booking = $this->getBooking($bookingId, $context);

        if ($booking->getStatus() == 'paid')
            return $booking;

        $date = [
            'id' => $bookingId,
            'status' => 'paid',
        ];

        $this->bookingRepository->update([
            $date
        ], $context);

        $itemCnt = $this->getReservationCount($booking->getItems());
        $customerId = $order->getOrderCustomer()->getCustomerId();
        $this->userService->updatePaidReservationCount($customerId, $itemCnt, $context);

        return $booking;
    }

}