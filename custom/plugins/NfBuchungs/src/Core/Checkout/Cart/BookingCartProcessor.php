<?php declare(strict_types=1);

namespace Nf\Booking\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Nf\Booking\Service\Booking\BookingServiceInterface;

readonly class BookingCartProcessor implements CartProcessorInterface
{
    public function __construct(
        private QuantityPriceCalculator $calculator,
        private BookingServiceInterface $bookingService
    )
    {
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $lineItems = $toCalculate->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        foreach ($lineItems as $item) {
            $bookingId = $item->getPayloadValue('nfBookingId');

            if (!$bookingId) {
                continue;
            }

            $price = $this->bookingService->getBookingPrice($bookingId, $context);

            if ($price == -1) {
                $toCalculate->remove($item->getId());
                $toCalculate->addErrors(
                    new \Shopware\Core\Checkout\Cart\Error\GenericCartError(
                        'booking-expired-' . $item->getId(),
                        'nf-booking.expired-message',
                        ['productName' => $item->getLabel()],
                        \Shopware\Core\Checkout\Cart\Error\Error::LEVEL_NOTICE,
                        true,
                        true,
                        false
                    )
                );
            }

            $taxRules = $item->getPriceDefinition()?->getTaxRules();
            if (!$taxRules) {
                $taxRules = $context->getTaxRules();
            }

            $definition = new QuantityPriceDefinition(
                $price,
                $taxRules,
                $item->getQuantity()
            );

            $item->setPriceDefinition($definition);

            $item->setPrice(
                $this->calculator->calculate($definition, $context)
            );

        }
    }
}