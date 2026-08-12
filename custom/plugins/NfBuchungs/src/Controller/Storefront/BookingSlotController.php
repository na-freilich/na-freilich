<?php declare(strict_types=1);

namespace Nf\Booking\Controller\Storefront;

use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Response;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\Routing\Annotation\Route;
use Nf\Booking\Service\Booking\BookingPriceServiceInterface;
use Nf\Booking\Service\Booking\BookingServiceInterface;
use Nf\Booking\Service\Booking\UserServiceInterface;
use Nf\Booking\Service\Booking\CreditServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nf\Booking\Core\Content\Booking\BookingQuery;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class BookingSlotController extends StorefrontController
{
    public function __construct(
        private readonly BookingPriceServiceInterface $priceService,
        private readonly BookingServiceInterface $bookingService,
        private readonly UserServiceInterface $userService,
        private readonly CreditServiceInterface $creditService,
    )
    {
    }

    #[Route(
        path: '/nf-booking/slots',
        name: 'frontend.booking.slots',
        defaults: ['XmlHttpRequest' => true],
        methods: ['GET']
    )]
    public function getSlots(Request $request, SalesChannelContext $context): Response
    {
        $date = $request->query->get('date');
        $locationId = $request->query->get('locationId');
        $productId = $request->query->get('productId');

        if (!$date || !$locationId || !$productId) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $query = new BookingQuery(
            context: $context->getContext(),
            date: $date,
            locationId: $locationId,
            productId: $productId,
            customerId: $context->getCustomer()?->getId(),
            token: $context->getToken(),
        );

        $slots = $this->priceService->getSlots($query);

        $firstSlot = reset($slots);
        $bookingStep = 60;

        if ($firstSlot) {
            $start = new \DateTime($firstSlot['start']);
            $end = new \DateTime($firstSlot['end']);
            $interval = $end->diff($start);
            $bookingStep = ($interval->h * 60) + $interval->i;
        }
        return $this->renderStorefront('@NfBooking/storefront/component/booking/slot-list.html.twig', [
            'slots' => $slots,
            'bookingStep' => $bookingStep
        ]);
    }

    #[Route(path: '/nf-booking/reserve', name: 'frontend.nf-booking.reserve', defaults: ['XmlHttpRequest' => true], methods: ['POST'])]
    public function reserve(Request $request, SalesChannelContext $context): JsonResponse
    {
        try {
            $query = new BookingQuery(
                context: $context->getContext(),
                date: $request->request->get('date'),
                timeStart: $request->request->get('timeStart'),
                timeEnd: $request->request->get('timeEnd'),
                locationId: $request->request->get('locationId'),
                productId: $request->request->get('productId'),
                customerId: $context->getCustomer()?->getId(),
                token: $context->getToken(),
            );

            $bookingId = $this->bookingService->reserve($query);

            return new JsonResponse([
                'success' => true,
                'bookingId' => $bookingId
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route(path: '/nf-booking/deleteSlot', name: 'frontend.nf-booking.delete-slot', defaults: ['XmlHttpRequest' => true], methods: ['POST'])]
    public function deleteSlot(Request $request, SalesChannelContext $context): JsonResponse
    {
        try {
            $data = [
                'slotId'  => $request->request->get('slotId'),
                'productId'  => $request->request->get('productId'),
                'timeStart'  => $request->request->get('timeStart'),
                'timeEnd'    => $request->request->get('timeEnd'),
            ];

            $query = new BookingQuery(
                context: $context->getContext(),
                productId: $request->request->get('productId'),
                timeStart: $request->request->get('timeStart'),
                timeEnd: $request->request->get('timeEnd'),
                customerId: $context->getCustomer()?->getId(),
                token: $context->getToken(),
            );

            foreach ($data as $key => $value) {
                if (empty($value)) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => "Missing parameter: {$key}"
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            $bookingId = $this->bookingService->deleteSlot($query, $data['slotId']);

            return new JsonResponse([
                'success' => true,
                'bookingId' => $bookingId,
                'message'   => 'Slot successfully deleted'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route(
        path: '/nf-booking/product-cart',
        name: 'frontend.booking.product.cart',
        defaults: ['XmlHttpRequest' => true],
        methods: ['GET']
    )]
    public function getProductCart(Request $request, SalesChannelContext $context): Response
    {
        $productId = $request->query->get('productId');

        if (!$productId) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $customerId =  $context->getCustomer()?->getId();

        $query = new BookingQuery(
            context: $context->getContext(),
            productId: $productId,
            customerId: $customerId,
            token: $context->getToken(),
        );
        $booking = $this->userService->getBookingCart($query);

        $slotCount = 0;
        if($booking)
        {
            $slotCount = $this->userService->calculateBookingSlots($booking);
            $slotCount -= $booking->getTotalCreditSlots();
        }

        $customerCredit = null;
        if ($customerId)
        {
            $customerCredit = $this->creditService->getCustomerCredit($customerId, $context->getContext());
            $customerCredit -= $booking?->getTotalCreditSlots();
        }


        return $this->renderStorefront('@NfBooking/storefront/component/booking/product-cart.html.twig', [
            'booking' => $booking,
            'productId' => $productId,
            'customerCredit' => $customerCredit,
            'slotCount' => $slotCount
        ]);
    }

    #[Route(
        path: '/nf-booking/item/delete',
        name: 'frontend.nf-booking.item.delete',
        defaults: ['XmlHttpRequest' => true],
        methods: ['POST']
    )]
    public function itemDelete(Request $request, SalesChannelContext $context): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $productId = $data['productId'] ?? null;
        $itemId = $data['itemId'] ?? null;

        if (!$productId || !$itemId) {
            return new JsonResponse('', Response::HTTP_BAD_REQUEST);
        }

        $query = new BookingQuery(
            context: $context->getContext(),
            productId: $productId,
            customerId: $context->getCustomer()?->getId(),
            token: $context->getToken(),
        );

        $success = $this->bookingService->deleteItem($query, $itemId);

        return new JsonResponse([
            'success' => $success,
        ]);
    }

}