<?php declare(strict_types=1);

namespace Nf\Booking\Core\Content\Booking;

use Shopware\Core\Framework\Context;
readonly class BookingQuery
{
    public function __construct(
        public Context $context,
        public ?string $date = null,
        public ?string $timeStart = null,
        public ?string $timeEnd = null,
        public ?string $locationId = null,
        public ?string $productId = null,
        public ?string $customerId = null,
        public ?string $adminId = null,
        public ?string $token = null
    ) {}
}