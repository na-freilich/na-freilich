<?php declare(strict_types=1);

namespace Nf\OrderDay\Service;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SystemConfig\SystemConfigService;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class OrderDayService
{
    public function __construct(
        private readonly EntityRepository $orderDayRepository,
        private readonly SystemConfigService $systemConfigService
    )
    {
    }

    public function getAvailableDays(SalesChannelContext $context): iterable
    {
        date_default_timezone_set('CET');
        $criteria = new Criteria();

        $criteria->addFilter(new EqualsFilter('active', true));

        $orderProcessCntDay = (int) $this->systemConfigService->get('OrderDay.config.orderProcessCntDay');
        $workTimeEnd = $this->systemConfigService->get('OrderDay.config.workTimeEnd');
        if ($orderProcessCntDay && $workTimeEnd)
        {
            if (date("H:i") > $workTimeEnd)
                $orderProcessCntDay++;

            $date = strtotime(date("Y-m-d"));
            $date = strtotime("+$orderProcessCntDay day", $date);
            $criteria->addFilter(new RangeFilter('date', [RangeFilter::GTE => date("Y-m-d", $date)]));
        }
        else
            $criteria->addFilter(new RangeFilter('date', [RangeFilter::GT => date("Y-m-d")]));

        return $this->orderDayRepository->search($criteria, $context->getContext())->getEntities();
    }

    public function updateOrderDay(array $order, Cart $cart, SalesChannelContext $context): array
    {
        $dayIds = $cart->getExtension('orderDays')->getVars();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $dayIds));

        $daysArr = $this->orderDayRepository->search($criteria, $context->getContext())->getEntities();
        $days = [];
        foreach ($daysArr as $day) {
            $days[] = $day->getDate();
        }

        if (count($days)) {
            $order['customFields']['nf_order_days'] = implode(", ", $days);
        }

        return $order;
    }
}
