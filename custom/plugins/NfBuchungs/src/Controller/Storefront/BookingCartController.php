<?php declare(strict_types=1);

namespace Nf\Booking\Controller\Storefront;

use Nf\Booking\Core\Content\Booking\BookingQuery;
use Nf\Booking\Service\Booking\UserServiceInterface;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Response;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class BookingCartController extends StorefrontController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly UserServiceInterface $userService
    )
    {
    }

    #[Route(
        path: '/nf-booking/add-to-cart',
        name: 'frontend.nf-booking.add-to-cart',
        defaults: [],
        methods: ['POST']
    )]
    public function addBookingProduct(RequestDataBag $data, SalesChannelContext $context): Response
    {
        $productId = $data->get('productId');
        $bookingId = $data->get('bookingId');

        $lineItem = new LineItem(
            $productId,
            LineItem::PRODUCT_LINE_ITEM_TYPE,
            $productId, 1);

        $lineItem->setRemovable(true);
        $lineItem->setPayloadValue('nfBookingId', $bookingId);

        $query = new BookingQuery(
            context: $context->getContext(),
            productId: $productId,
            customerId: $context->getCustomer()?->getId(),
            token: $context->getToken(),
        );
        $booking = $this->userService->getBooking($query);

        $bookingDescription = $this->renderStorefront('@NfBooking/storefront/component/booking/product-cart-description.html.twig', [
            'booking' => $booking,
            'productId' => $productId
        ])->getContent();

        $lineItem->setPayloadValue('nfBookingDescription', $bookingDescription);

        try {
            $productId = $lineItem->getReferencedId();
            $cart = $this->cartService->getCart($context->getToken(), $context);
            foreach ($cart->getLineItems() as $existingItem) {
                if ($existingItem->getReferencedId() === $productId) {
                    $this->cartService->remove($cart, $existingItem->getId(), $context);
                }
            }

            $this->cartService->add($cart, $lineItem, $context);

        } catch (\Exception $e) {
            $this->addFlash('danger', 'Error adding reservation');
        }

        return $this->redirectToRoute('frontend.checkout.cart.page');
    }

    #[Route(
        path: '/nf-booking/check-expired',
        name: 'frontend.nf-booking.check-expired',
        defaults: ['XmlHttpRequest' => true],
        methods: ['GET']
    )]
    public function checkExpired(RequestDataBag $data, SalesChannelContext $context): JsonResponse
    {
        $this->userService->checkExpired($context);

        return new JsonResponse([
            'success' => true
        ]);
    }
}