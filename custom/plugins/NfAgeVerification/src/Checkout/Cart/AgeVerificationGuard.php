<?php declare(strict_types=1);

namespace NfAgeVerification\Checkout\Cart;

use NfAgeVerification\Service\CustomFieldService;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RequestStack;

class AgeVerificationGuard
{
    public const COOKIE_NAME = 'nf_is_adult';

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function isActive(?string $salesChannelId): bool
    {
        return (bool) $this->systemConfigService->get('NfAgeVerification.config.active', $salesChannelId);
    }

    /**
     * The cookie is set in the browser after a click, never by the server.
     * Without a storefront request (order workers, CLI) the cart must stay intact.
     */
    public function isAdult(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return true;
        }

        return $request->cookies->get(self::COOKIE_NAME) === '1';
    }

    public function isRestrictedLineItem(LineItem $lineItem): bool
    {
        if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
            return false;
        }

        $customFields = $lineItem->getPayloadValue('customFields');
        if (!\is_array($customFields)) {
            return false;
        }

        return self::isRestrictedValue($customFields[CustomFieldService::AGE_RESTRICTION_ACTIVE] ?? null);
    }

    public static function isRestrictedValue(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }
}
