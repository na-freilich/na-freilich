<?php declare(strict_types=1);

namespace Nf\Booking\Controller\Api;

use Nf\Booking\Core\Content\Booking\BookingQuery;
use Nf\Booking\Service\Booking\BookingPriceServiceInterface;
use Nf\Booking\Service\Booking\BookingServiceInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Context;
use Nf\Booking\Service\Booking\AdminServiceInterface;
use Shopware\Core\Framework\Api\Context\AdminApiSource;

#[Route(defaults: ['_routeScope' => ['api']])]
class BookingAdminController extends AbstractController
{

    public function __construct(
        private readonly BookingPriceServiceInterface $priceService,
        private readonly AdminServiceInterface $adminService,
        private readonly BookingServiceInterface $bookingService,
    )
    {
    }

    #[Route(
        path: '/api/_action/nf-booking/slots',
        name: 'api.action.nf-booking.slots',
        methods: ['POST']
    )]
    public function getAdminSlots(RequestDataBag $data, Context $context): JsonResponse
    {
        $date = $data->get('date');
        $locationId = $data->get('locationId');
        if (!$date || !$locationId) {
            return new JsonResponse([
                'success' => true,
                'data' => []
            ]);
        }

        $source = $context->getSource();
        $adminId = ($source instanceof AdminApiSource) ? $source->getUserId() : null;

        $query = new BookingQuery(
            context: $context,
            date: substr($date, 0, 10),
            locationId: $locationId,
            productId: "",
            adminId: $adminId
        );

        $slots = $this->priceService->getSlots($query);

        return new JsonResponse([
            'success' => true,
            'data' => $slots
        ]);
    }

    #[Route(
        path: '/api/_action/nf-booking/booking',
        name: 'api.action.nf-booking.booking',
        methods: ['POST']
    )]
    public function getBooking(RequestDataBag $data, Context $context): JsonResponse
    {
        $slotId = $data->get('slotId');
        if (!$slotId) {
            return new JsonResponse('', Response::HTTP_BAD_REQUEST);
        }

        $data =[
            'slotId' => $slotId,
        ];

        $booking = $this->adminService->getBooking($data, $context);

        return new JsonResponse([
            'success' => true,
            'booking' => $booking
        ]);
    }

    #[Route(
        path: '/api/_action/nf-booking/slot/block',
        name: 'api.action.nf-booking.slot.block',
        methods: ['POST']
    )]
    public function blockSlot(RequestDataBag $data, Context $context): JsonResponse
    {
        $source = $context->getSource();
        $adminId = ($source instanceof AdminApiSource) ? $source->getUserId() : null;

        try {
            $query = new BookingQuery(
                context: $context,
                date: $data->get('date'),
                timeStart: $data->get('start'),
                timeEnd: $data->get('end'),
                locationId: $data->get('locationId'),
                productId: "",
                adminId: $adminId
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

    #[Route(
        path: '/api/_action/nf-booking/slot/unblock',
        name: 'api.action.nf-booking.slot.unblock',
        methods: ['POST']
    )]
    public function unblockSlot(RequestDataBag $data, Context $context): JsonResponse
    {
        $source = $context->getSource();
        $adminId = ($source instanceof AdminApiSource) ? $source->getUserId() : null;
        $slotId =$data->get('slotId');
        try {
            $query = new BookingQuery(
                context: $context,
                date: $data->get('date'),
                timeStart: $data->get('start'),
                timeEnd: $data->get('end'),
                locationId: $data->get('locationId'),
                productId: "",
                adminId: $adminId
            );

            $bookingId = $this->bookingService->deleteSlot($query, $slotId);

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
}