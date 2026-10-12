<?php declare(strict_types=1);

namespace NfAgeVerification\Checkout\Cart;

use NfAgeVerification\Checkout\Cart\Error\AgeRestrictionCartError;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AgeRestrictionCartProcessor implements CartProcessorInterface
{
    public function __construct(
        private readonly AgeVerificationGuard $guard,
    ) {
    }

    public function process(
        CartDataCollection $data,
        Cart $original,
        Cart $toCalculate,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        if (!$this->guard->isActive($context->getSalesChannelId()) || $this->guard->isAdult()) {
            $this->stripOwnErrors($toCalculate);

            return;
        }

        $lineItems = $toCalculate->getLineItems();
        $removedNames = [];

        foreach ($lineItems->getElements() as $lineItem) {
            if (!$this->guard->isRestrictedLineItem($lineItem)) {
                continue;
            }

            $removedNames[$lineItem->getId()] = (string) ($lineItem->getLabel() ?: $lineItem->getId());
        }

        foreach ($removedNames as $lineItemId => $name) {
            $lineItems->remove($lineItemId);
            $this->removeDeliveryPositions($toCalculate, $lineItemId);
            $toCalculate->addErrors(new AgeRestrictionCartError($lineItemId, $name));
        }
    }

    private function stripOwnErrors(Cart $cart): void
    {
        $errorIds = [];

        foreach ($cart->getErrors() as $error) {
            if ($error instanceof AgeRestrictionCartError && !$error->blockOrder()) {
                $errorIds[] = $error->getId();
            }
        }

        foreach ($errorIds as $errorId) {
            $cart->getErrors()->remove($errorId);
        }
    }

    private function removeDeliveryPositions(Cart $cart, string $lineItemId): void
    {
        foreach ($cart->getDeliveries() as $delivery) {
            $positions = $delivery->getPositions();
            $positionIds = [];

            foreach ($positions->getElements() as $position) {
                if ($position->getIdentifier() === $lineItemId) {
                    $positionIds[] = $position->getIdentifier();
                }
            }

            foreach ($positionIds as $positionId) {
                $positions->remove($positionId);
            }
        }
    }
}
