<?php declare(strict_types=1);

namespace Nf\ProductReviewRequest\Checkout;

final readonly class CheckoutConsent
{
    public function __construct(
        public bool $newsletterSubscribed,
    ) {
    }

    /**
     * The product review mail is created only when the customer opted into the newsletter.
     */
    public function isReviewEligible(): bool
    {
        return $this->newsletterSubscribed;
    }
}
