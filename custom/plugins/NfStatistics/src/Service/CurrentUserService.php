<?php declare(strict_types=1);

namespace Nf\Statistics\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;

class CurrentUserService
{
    public function __construct(
        private readonly EntityRepository $currentUsersRepository,
        private readonly EntityRepository $visitorsRepository,
        private readonly Connection $connection
    )
    {
    }

    public function getOnline(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $now = new \DateTimeImmutable();
        $dateInterval = new \DateInterval('PT3M');
        $thresholdDate = $now->sub($dateInterval);

        $criteria->addFilter(
            new RangeFilter('createdAt', [
                RangeFilter::GTE => $thresholdDate->format(Defaults::STORAGE_DATE_TIME_FORMAT)
            ])
        );

        $criteria->addAggregation(
            new CountAggregation('count-visitors', 'remoteAddr')
        );

        $result = $this->currentUsersRepository->search($criteria, $context);
        $aggregation = $result->getAggregations()->get('count-visitors');

        return $aggregation->getCount();
    }

    public function getVisitors(Request $request, Context $context, int &$total): array
    {
        $page = (int)$request->request->get('page') ?: 1;
        $limit = (int)$request->request->get('limit') ?: 25;

        $dateFrom = $request->request->get('dateFrom') == ''? null : $request->request->get('dateFrom');
        $dateTo = $request->request->get('dateTo') == ''? null : $request->request->get('dateTo');

        $where = [];
        $whereSql = '';
        $offset = ( $page - 1 ) * $limit;
        $limit = " LIMIT $limit OFFSET $offset";
        if (!is_null($dateFrom) && !is_null($dateTo))
        {
            $where[] = 'date >= "'.$dateFrom.'" and date <= "'.$dateTo.'"';
        }
        elseif (!is_null($dateFrom) && is_null($dateTo))
        {
            $where[] = 'date >= "'.$dateFrom.'"';
        }
        elseif (is_null($dateFrom) && !is_null($dateTo))
        {
            $where[] = 'date <= "'.$dateTo.'"';
        }

        if (sizeof($where))
        {
            $whereSql = ' where '.implode(' and ', $where);
        }

        $orderBySql = ' order by date';

        $sql = '
                select
                    date,
                    sum(unique_visits) all_visits,
                    sum(IF(device_type="tablet", unique_visits, 0) ) tablet_visits,
                    sum(IF(device_type="mobile", unique_visits, 0) ) mobile_visits,
                    sum(IF(device_type="desktop", unique_visits, 0) ) desktop_visits
                from nf_stat_visitors as sv
                '.$whereSql.'
                group by date
                '.$orderBySql . $limit.'
            ';

        $sql_total ='
                select
                    COUNT(distinct (date)) cnt_day
                from nf_stat_visitors
                '.$whereSql.'
            ';

        $stmt = $this->connection->prepare($sql);


        $result = $stmt->executeQuery()->fetchAllAssociative();

        $total = $this->connection->executeQuery(
            $sql_total
        )->fetchOne();

        return $result;
    }

    public function getConversion(Request $request, Context $context, int &$total): array
    {
        $page = (int)$request->request->get('page') ?: 1;
        $limit = (int)$request->request->get('limit') ?: 25;

        $dateFrom = $request->request->get('dateFrom') == ''? null : $request->request->get('dateFrom');
        $dateTo = $request->request->get('dateTo') == ''? null : $request->request->get('dateTo');

        $where = [];
        $whereSql = '';
        $offset = ( $page - 1 ) * $limit;
        $limit = " LIMIT $limit OFFSET $offset";
        if (!is_null($dateFrom) && !is_null($dateTo))
        {
            $where[] = 'date >= "'.$dateFrom.'" and date <= "'.$dateTo.'"';
        }
        elseif (!is_null($dateFrom) && is_null($dateTo))
        {
            $where[] = 'date >= "'.$dateFrom.'"';
        }
        elseif (is_null($dateFrom) && !is_null($dateTo))
        {
            $where[] = 'date <= "'.$dateTo.'"';
        }

        if (sizeof($where))
        {
            $whereSql = ' where '.implode(' and ', $where);
        }

        $orderBySql = ' order by date';

        $sql = '
                select
                    date,
                    sum(unique_visits) all_visits,
                    sum(page_impressions) page_views,
                    (select count(*) from  `order` o
                    where date(created_at) = sv.date) order_cnt,
                    (select sum(amount_total) from  `order` o
                    where date(created_at) = sv.date) order_sum
                    

                from nf_stat_visitors as sv
                '.$whereSql.'
                group by date
                '.$orderBySql . $limit.'
            ';

        $sql_total ='
                select
                    COUNT(distinct (date)) cnt_day
                from nf_stat_visitors
                '.$whereSql.'
            ';

        $stmt = $this->connection->prepare($sql);


        $result = $stmt->executeQuery()->fetchAllAssociative();

        $total = $this->connection->executeQuery(
            $sql_total
        )->fetchOne();

        return $result;
    }

    public function updateStatistic(SalesChannelContext $context, Request $request)
    {
        $id = Uuid::randomHex();
        $token = $request->get('key');
        if(!$token)
            $token = $context->getToken();

        $today = (new \DateTimeImmutable)
            ->setTime(0, 0, 0);

        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(
            new RangeFilter('createdAt', [
                RangeFilter::GTE => $today->format(Defaults::STORAGE_DATE_TIME_FORMAT)
            ])
        );
        $criteria->addFilter(new EqualsFilter('token', $token));
        $new = !$this->currentUsersRepository->search($criteria, $context->getContext())->count();
        $customerId = $context->getCustomerId();
        $remoteAddr = $request->getClientIp();
        $httpUserAgent = (string)$_SERVER['HTTP_USER_AGENT'];
        $deviceType = DeviceService::getType($httpUserAgent);
        $this->currentUsersRepository->upsert([
            [
                'id' => $id,
                'remoteAddr' => $remoteAddr,
                'userId' => $customerId,
                'token' => $token,
                'deviceType' => $deviceType,
            ],
        ], $context->getContext());

        $salesChannelId = $context->getSalesChannelId();
        $criteriaVisitors = new Criteria();
        $criteriaVisitors->setLimit(1);
        $criteriaVisitors->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        $criteriaVisitors->addFilter(new EqualsFilter('date', $today->format(Defaults::STORAGE_DATE_TIME_FORMAT)));
        $criteriaVisitors->addFilter(new EqualsFilter('deviceType', $deviceType));

        $visitors = $this->visitorsRepository->search($criteriaVisitors, $context->getContext())->first();

        if($visitors)
        {
            $pageImpressions = $visitors->getPageImpressions();
            $uniqueVisits = $visitors->getUniqueVisits();
            $visitorId = $visitors->getId();
            $pageImpressions++;
            if($new)
            {
                $uniqueVisits++;
            }
        }
        else
        {
            $pageImpressions = 1;
            $uniqueVisits = 1;
            $visitorId = Uuid::randomHex();
        }

        $data[] = [
            'id' => $visitorId,
            'salesChannelId' => $salesChannelId,
            'date' => $today->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'pageImpressions' => $pageImpressions,
            'uniqueVisits' => $uniqueVisits,
            'deviceType' => $deviceType
        ];

        $this->visitorsRepository->upsert($data, $context->getContext());
    }
}