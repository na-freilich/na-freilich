<?php declare(strict_types=1);

namespace Nf\Booking\Controller\Storefront;

use Nf\Booking\Core\Content\Booking\BookingQuery;
use Nf\Booking\Service\Booking\CreditServiceInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class BookingCreditController extends StorefrontController
{

    public function __construct(
        private readonly CreditServiceInterface $creditService
    )
    {
    }

    #[Route(
        path: '/nf-booking/credit/apply',
        name: 'frontend.nf-booking.credit.add',
        defaults: ['XmlHttpRequest' => true],
        methods: ['POST']
    )]
    public function applyCredit(RequestDataBag $data, SalesChannelContext $context): JsonResponse
    {
            $slotsCount = (float)$data->get('slotsCount');
        $customerId = $context->getCustomer()?->getId();

        if (!$slotsCount || !$customerId) {
            return new JsonResponse('', Response::HTTP_BAD_REQUEST);
        }

        try {
            $query = new BookingQuery(
                context: $context->getContext(),
                customerId: $context->getCustomer()?->getId()
            );

            $this->creditService->applyCredit($query, $slotsCount);

            return new JsonResponse([
                'success' => true,
                'message' => $this->trans('nf-booking.credit.successApplied')
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->trans($e->getMessage())
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(
        path: '/nf-booking/credit/remove',
        name: 'frontend.nf-booking.credit.remove',
        defaults: ['XmlHttpRequest' => true],
        methods: ['POST']
    )]
    public function creditRemove(RequestDataBag $data, SalesChannelContext $context): JsonResponse
    {

        try {
            $query = new BookingQuery(
                context: $context->getContext(),
                customerId: $context->getCustomer()?->getId()
            );

            $this->creditService->RemoveCredit($query);

            return new JsonResponse([
                'success' => true,
                'message' => $this->trans('nf-booking.credit.successRemove')
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->trans($e->getMessage())
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}