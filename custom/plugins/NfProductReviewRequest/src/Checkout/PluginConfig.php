<?php declare(strict_types=1);

namespace Nf\ProductReviewRequest\Checkout;

final class PluginConfig
{
    public const string ACTIVE = 'NfProductReviewRequest.config.active';

    public const string DELAY_DAYS = 'NfProductReviewRequest.config.delayDays';

    public const string SHIPPED_STATE = 'shipped';
}
