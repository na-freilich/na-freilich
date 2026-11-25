<?php declare(strict_types=1);

namespace Nf\ProductReviewRequest\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

final class ProductReviewRequestTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'nf.product_review_request.dispatch';
    }

    public static function getDefaultInterval(): int
    {
        return self::HOURLY;
    }

    public static function shouldRescheduleOnFailure(): bool
    {
        return true;
    }
}
