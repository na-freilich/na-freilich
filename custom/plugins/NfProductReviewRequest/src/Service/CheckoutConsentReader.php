<?php declare(strict_types=1);

namespace Nf\ProductReviewRequest\Service;

use Nf\ProductReviewRequest\Checkout\CheckoutConsent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class CheckoutConsentReader
{
    public const string NEWSLETTER_FIELD = 'newsletter';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function read(): CheckoutConsent
    {
        return new CheckoutConsent(
            $this->isChecked($this->rawValue($this->requestStack->getCurrentRequest(), self::NEWSLETTER_FIELD)),
        );
    }

    private function rawValue(?Request $request, string $field): mixed
    {
        if (!$request instanceof Request) {
            return null;
        }

        if ($request->request->has($field)) {
            return $request->request->all()[$field] ?? $request->request->get($field);
        }

        $payload = $request->getPayload();
        if ($payload->has($field)) {
            return $payload->all()[$field] ?? $payload->get($field);
        }

        return null;
    }

    private function isChecked(mixed $value): bool
    {
        if (\is_array($value)) {
            foreach ($value as $item) {
                if ($this->isChecked($item)) {
                    return true;
                }
            }

            return false;
        }

        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value)) {
            return $value === 1;
        }

        if (!\is_string($value)) {
            return false;
        }

        return \in_array(strtolower(trim($value)), ['1', 'on', 'true', 'yes'], true);
    }
}
