<?php declare(strict_types=1);

namespace Nf\OrderDay\Storefront\Controller;

use Shopware\Core\Checkout\Cart\AbstractCartPersister;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class CheckoutController extends StorefrontController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly CartService $cartService,
        private readonly AbstractCartPersister $persister,
    ) {
    }
    #[Route(
        path: '/nf-order-day/update',
        name: 'frontend.nf-order-day.update',
        defaults: ['XmlHttpRequest' => true],
        methods: ['GET', 'POST']
    )]
    public function updateDays(Request $request, SalesChannelContext $context): Response
    {
        $orderDays = [];
        if($request->get('days'))
        {
            $orderDays = explode(',', $request->get('days'));
        }

        $cart = $this->cartService->getCart($context->getToken(), $context);
        $cart->addExtension("orderDays", new ArrayStruct($orderDays));
        $this->persister->save($cart, $context);
        $cart = $this->cartService->recalculate($cart, $context);

        $page['cart'] = $cart;
        return $this->renderStorefront('@Storefront/storefront/page/checkout/summary.html.twig', [
            'page' => $page,
            'context' => $context
        ]);
    }
}
