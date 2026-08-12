<?php declare(strict_types=1);

namespace Nf\Booking\Controller\Storefront;

use Nf\Booking\Core\Content\Booking\BookingItem\NfBookingItemEntity;
use Nf\Booking\Service\Booking\UserServiceInterface;
use Nf\Booking\Service\Booking\BookingServiceInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Response;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Nf\Booking\Core\Content\Booking\BookingItem\NfBookingItemCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Shopware\Core\Checkout\Cart\Order\RecalculationService;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Framework\Context;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class BookingOrderController extends StorefrontController
{
    public function __construct(
        private readonly EntityRepository $bookingItemRepository,
        private readonly EntityRepository $bookingRepository,
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $orderLineItemRepository,
        private readonly EntityRepository $creditRepository,

        private readonly UserServiceInterface $userService,
        private readonly BookingServiceInterface $bookingService,
        private readonly RecalculationService $recalculationService,
    )
    {
    }

    #[Route(
        path: '/nf-booking/order/items',
        name: 'frontend.booking.order.items',
        defaults: ['XmlHttpRequest' => true],
        methods: ['GET']
    )]
    public function getItems(Request $request, SalesChannelContext $context): Response
    {
        $orderId = $request->query->get('orderId');
        if (!$orderId) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $criteria = new Criteria([$orderId]);
        $criteria->addFilter(new EqualsFilter('orderCustomer.customerId', $context->getCustomer()->getId()));
        $criteria->addAssociation('transactions.stateMachineState');
        $criteria->addAssociation('lineItems');

        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $context->getContext())->first();
        $isPaid = $this->isPaid($order);

        if (!$order) {
            throw new AccessDeniedHttpException('Order not found');
        }

        $bookingIds = [];
        if ($order->getLineItems()) {
            foreach ($order->getLineItems() as $lineItem) {
                $payload = $lineItem->getPayload();

                if (isset($payload['nfBookingId'])) {
                    $bookingIds[] = $payload['nfBookingId'];
                }
            }
        }

        if (empty($bookingIds)) {
            return $this->renderStorefront('@NfBooking/storefront/page/account/order-history/booking-modal-fields.html.twig', [
                'bookingItems' => [],
                'orderId' => $orderId
            ]);
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('bookingId', $bookingIds));
        $criteria->addAssociation('location');

        $criteria->addSorting(new FieldSorting('location.name', FieldSorting::ASCENDING))
            ->addSorting(new FieldSorting('bookingDate', FieldSorting::ASCENDING))
            ->addSorting(new FieldSorting('startTime', FieldSorting::ASCENDING));

        /** @var NfBookingItemCollection $bookingCollection */
        $bookingCollection = $this->bookingItemRepository->search($criteria, $context->getContext())->getEntities();

        $bookingItems = $this->userService->mergeItems($bookingCollection);

        foreach ($bookingItems as $item) {
            $slotCnt = $this->getSlotsCnt($item);

            $item->addExtension('time', new ArrayStruct([
                'slotCnt' => $slotCnt,
            ]));
        }

        return $this->renderStorefront('@NfBooking/storefront/page/account/order-history/booking-modal-fields.html.twig', [
            'bookingItems' => $bookingItems,
            'orderId' => $orderId,
            'isPaid' => $isPaid,
        ]);
    }

    private function getSlotsCnt(NfBookingItemEntity $item): float
    {
        $start = $item->getStartTime();
        $end = $item->getEndTime();

        if ($start instanceof \DateTimeInterface && $end instanceof \DateTimeInterface) {
            $totalSeconds = ($end->getTimestamp() - $start->getTimestamp());
        } else {
            $totalSeconds = (strtotime((string)$end) - strtotime((string)$start));
        }

        return $totalSeconds / 3600;
    }

    private function isPaid($order): bool
    {
        $lastTransaction = $order->getTransactions()?->last();
        $paymentState = $lastTransaction?->getStateMachineState()?->getTechnicalName();

        return ($paymentState !== 'open');
    }

    #[Route(
        path: '/nf-booking/order/cancel',
        name: 'frontend.booking.order.cancel',
        defaults: ['XmlHttpRequest' => true],
        methods: ['POST']
    )]
    public function cancel(Request $request, SalesChannelContext $context): Response
    {
        if (!$context->getCustomer()) {
            throw new CustomerNotLoggedInException();
        }

        $customerId = $context->getCustomer()->getId();

        $orderId = $request->request->get('orderId');
        $rawBookingIds = $request->request->all('bookingItemIds');

        if (!$orderId || empty($rawBookingIds)) {
            return new JsonResponse(['success' => false, 'message' => 'Missing data'], Response::HTTP_BAD_REQUEST);
        }

        $bookingItemIds = [];
        foreach ($rawBookingIds as $rawId) {
            $parts = explode('|', (string)$rawId);
            foreach ($parts as $part) {
                $trimmed = trim($part);
                if ($trimmed !== '') {
                    $bookingItemIds[] = $trimmed;
                }
            }
        }

        $criteria = new Criteria([$orderId]);
        $criteria->addFilter(new EqualsFilter('orderCustomer.customerId', $context->getCustomer()->getId()));
        $criteria->addAssociation('transactions.stateMachineState');
        $criteria->addAssociation('lineItems');

        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $context->getContext())->first();

        if (!$order) {
            return new JsonResponse(['success' => false, 'message' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $itemCriteria = new Criteria($bookingItemIds);
        $itemCriteria->addAssociation('booking');

        /** @var NfBookingItemCollection $bookingCollection */
        $bookingCollection = $this->bookingItemRepository->search($itemCriteria, $context->getContext())->getEntities();
        $bookingId = null;
        $deletePayload = [];
        $slotCnt = 0;
        foreach ($bookingCollection as $bookingItem) {
            $booking = $bookingItem->getBooking();

            if (!$booking) {
                throw new AccessDeniedHttpException('Booking not found for item ' . $bookingItem->getId());
            }

            if ($booking->getCustomerId() !== $customerId) {
                throw new AccessDeniedHttpException('Security breach: Booking item does not belong to the current customer.');
            }

            $bookingId = $booking->getId();
            $deletePayload[] = ['id' => trim($bookingItem->getId())];
            $slotCnt += $this->getSlotsCnt($bookingItem);
        }

        if (!$bookingId) {
            throw new AccessDeniedHttpException('Booking not found');
        }

        $this->bookingItemRepository->delete(
            $deletePayload,
            $context->getContext()
        );

        $this->bookingService->updateBookingTotalPrice($bookingId, $context->getContext());
        $isPaid = $this->isPaid($order);

        $lineItemCriteria = new Criteria();
        $lineItemCriteria->addFilter(new EqualsFilter('orderId', $orderId));
        $lineItemCriteria->addFilter(new EqualsFilter('payload.nfBookingId', $bookingId));

        /** @var OrderLineItemEntity|null $lineItem */
        $lineItem = $this->orderLineItemRepository->search($lineItemCriteria, $context->getContext())->first();

        $orderCriteria = new Criteria([$bookingId]);
        $orderCriteria->addAssociation('items');
        $orderCriteria->addAssociation('items.location');
        $orderCriteria->getAssociation('items')
            ->addSorting(new FieldSorting('location.name', FieldSorting::ASCENDING))
            ->addSorting(new FieldSorting('bookingDate', FieldSorting::ASCENDING))
            ->addSorting(new FieldSorting('startTime', FieldSorting::ASCENDING));

        $updatedBooking = $this->bookingRepository->search($orderCriteria, $context->getContext())->first();

        try {
            if (!$isPaid) {
                $newPrice = 0;
                if ($updatedBooking)
                    $newPrice = $updatedBooking->getTotalPrice();


                if ($lineItem) {

                    $versionId = $this->orderRepository->createVersion($orderId, $context->getContext());
                    $versionContext = $context->getContext()->createWithVersionId($versionId);

                    if ($updatedBooking)
                    {
                        $bookingDescription = $this->renderStorefront('@NfBooking/storefront/component/booking/product-cart-description.html.twig', [
                            'booking' => $updatedBooking,
                            'productId' => $lineItem->getProductId()
                        ])->getContent();
                    } else
                        $bookingDescription = '';

                    $currentPayload = $lineItem->getPayload() ?? [];

                    $updatedPayload = array_merge($currentPayload, [
                        'nfBookingDescription' => $bookingDescription
                    ]);

                    $this->orderLineItemRepository->update([
                        [
                            'id' => $lineItem->getId(),
                            'productId' => $lineItem->getProductId(),
                            'referencedId' => $lineItem->getReferencedId(),
                            'unitPrice' => $newPrice,
                            'totalPrice' => $newPrice,
                            'price' => new CalculatedPrice(
                                $newPrice,
                                $newPrice,
                                $lineItem->getPrice()->getCalculatedTaxes(),
                                $lineItem->getPrice()->getTaxRules()
                            ),
                            'payload' => $updatedPayload
                        ]
                    ], $versionContext);

                    $this->recalculationService->recalculate($orderId, $versionContext);
                    $context->getContext()->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($versionId) {
                        $this->orderRepository->merge($versionId, $context);
                    });
                }
                $message = 'Items removed and order recalculated.';
            } else {

                $this->creditRepository->create([
                    [
                        'id' => Uuid::randomHex(),
                        'customerId' => $customerId,
                        'totalSlots' => $slotCnt,
                        'usedSlots' => 0,
                        'comment' => "stornierte buchung",
                        'active' => true,
                    ]
                ], $context->getContext());

                if ($lineItem) {
                    if ($updatedBooking)
                    {
                        $bookingDescription = $this->renderStorefront('@NfBooking/storefront/component/booking/product-cart-description.html.twig', [
                            'booking' => $updatedBooking,
                            'productId' => $lineItem->getProductId()
                        ])->getContent();
                    } else
                        $bookingDescription = '';

                    $currentPayload = $lineItem->getPayload() ?? [];

                    $updatedPayload = array_merge($currentPayload, [
                        'nfBookingDescription' => $bookingDescription
                    ]);

                    $this->orderLineItemRepository->update([
                        [
                            'id' => $lineItem->getId(),
                            'productId' => $lineItem->getProductId(),
                            'referencedId' => $lineItem->getReferencedId(),
                            'payload' => $updatedPayload
                        ]
                    ], $context->getContext());
                }

                $message = 'Order is paid. Bonus points have been credited to your account.';
            }

            return new JsonResponse([
                'success' => true,
                'message' => $message,
                'isPaid' => $isPaid
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}