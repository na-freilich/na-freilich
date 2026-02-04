<?php declare(strict_types=1);

namespace Nf\OrderDay\Core\Checkout\Cart\Price;

use Shopware\Core\Checkout\Cart\AbstractCartPersister;
use Shopware\Core\Checkout\Cart\Exception\CartTokenNotFoundException;
use Shopware\Core\Checkout\Cart\Price\AmountCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceCollection;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AmountCalculatorDecorator extends AmountCalculator
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AmountCalculator $originalAmountCalculator,
//        private readonly CartService $cartService
        private readonly AbstractCartPersister $persister,
    ){
    }

    public function calculate(PriceCollection $prices, PriceCollection $shippingCosts, SalesChannelContext $context): CartPrice
    {

        $cartPrice = $this->originalAmountCalculator->calculate($prices, $shippingCosts, $context);
        try {
            $cart = $this->persister->load($context->getToken(), $context);
        } catch (CartTokenNotFoundException) {
            return $cartPrice;
        }

        $orderDays = $cart->getExtension('orderDays');
        if (!$orderDays)
            return $cartPrice;

        $cntDays = $orderDays->count();
        if ($cntDays < 2)
            return $cartPrice;

        foreach($cartPrice->getCalculatedTaxes()as $tax) {
            $tax->setTax($cntDays * $tax->getTax());
        }

        return new CartPrice(
            $cntDays * $cartPrice->getTotalPrice(),
            $cntDays * $cartPrice->getNetPrice(),
            $cntDays * $cartPrice->getPositionPrice(),
            $cartPrice->getCalculatedTaxes(),
            $cartPrice->getTaxRules(),
            $cartPrice->getTaxStatus(),
            $cntDays * $cartPrice->getRawTotal()
        );
    }

}