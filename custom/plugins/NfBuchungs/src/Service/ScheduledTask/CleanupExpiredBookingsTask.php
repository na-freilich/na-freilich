<?php declare(strict_types=1);

namespace Nf\Booking\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;
class CleanupExpiredBookingsTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'nf_booking.cleanup_expired';
    }

    public static function getDefaultInterval(): int
    {
        return 60;
    }
}