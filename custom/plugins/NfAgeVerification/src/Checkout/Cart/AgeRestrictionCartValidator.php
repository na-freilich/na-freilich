<?php declare(strict_types=1);

namespace NfAgeVerification\Checkout\Cart;

use NfAgeVerification\Checkout\Cart\Error\AgeRestrictionCartError;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AgeRestrictionCartValidator implements CartValidatorInterface
{
    public function __construct(
        private readonly AgeVerificationGuard $guard,
    ) {
    }

    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        if (!$this->guard->isActive($context->getSalesChannelId()) || $this->guard->isAdult()) {
            return;
        }

        foreach ($cart->getLineItems() as $lineItem) {
            if (!$this->guard->isRestrictedLineItem($lineItem)) {
                continue;
            }

            $errors->add(new AgeRestrictionCartError(
                $lineItem->getId(),
                (string) ($lineItem->getLabel() ?: $lineItem->getId()),
                true,
            ));
        }
    }
}
