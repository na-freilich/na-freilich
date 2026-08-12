<?php declare(strict_types=1);

namespace Nf\Booking\Service\Booking;

use Nf\Booking\Core\Content\Booking\BookingQuery;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
interface BookingPriceServiceInterface
{
    public function getSlots(BookingQuery $query): ?array;
}