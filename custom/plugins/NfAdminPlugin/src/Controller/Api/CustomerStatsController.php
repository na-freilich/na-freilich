<?php declare(strict_types=1);

namespace Nf\AdminPlugin\Controller\Api;

use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\DateHistogramResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\SumResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class CustomerStatsController extends AbstractController
{
    /**
     * @param EntityRepository<CustomerCollection> $customerRepository
     * @param EntityRepository<OrderCollection> $orderRepository
     */
    public function __construct(
        private readonly EntityRepository $customerRepository,
        private readonly EntityRepository $orderRepository
    ) {
    }

    #[Route(
        path: '/api/_action/customer-stats/turnover/{customerId}',
        name: 'api.action.customer_stats.turnover',
        methods: ['GET'],
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['customer:read', 'order:read']]
    )]
    public function getTurnover(string $customerId, Context $context): JsonResponse
    {
        if (!Uuid::isValid($customerId)) {
            throw new BadRequestHttpException('customerId must be a valid UUID.');
        }

        $customer = $this->customerRepository->search(new Criteria([$customerId]), $context)->first();
        if (!$customer instanceof CustomerEntity) {
            throw new NotFoundHttpException('Customer not found.');
        }

        $year = (int) date('Y');
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('orderCustomer.customerId', $customerId));
        $criteria->addFilter(new EqualsFilter('stateMachineState.technicalName', OrderStates::STATE_COMPLETED));
        $criteria->addFilter(new RangeFilter('orderDateTime', [
            RangeFilter::GTE => sprintf('%d-01-01 00:00:00', $year),
            RangeFilter::LTE => sprintf('%d-12-31 23:59:59', $year),
        ]));
        $criteria->addAggregation(
            new DateHistogramAggregation(
                'turnover-per-month',
                'orderDateTime',
                DateHistogramAggregation::PER_MONTH,
                null,
                new SumAggregation('turnover', 'amountTotal')
            )
        );

        $monthlyTurnover = [];
        $totalYear = 0.0;
        $aggregation = $this->orderRepository->search($criteria, $context)->getAggregations()->get('turnover-per-month');
        if ($aggregation instanceof DateHistogramResult) {
            foreach ($aggregation->getBuckets() as $bucket) {
                $sum = $bucket->getResult();
                if (!$sum instanceof SumResult) {
                    continue;
                }

                $key = (string) $bucket->getKey();
                $month = (int) substr($key, 5, 2);
                if ($month < 1 || $month > 12 || $sum->getSum() <= 0) {
                    continue;
                }

                $monthlyTurnover[$month] = $sum->getSum();
                $totalYear += $sum->getSum();
            }
        }

        return new JsonResponse([
            'year' => $year,
            'totalYear' => $totalYear,
            'totalLifetime' => $customer->getOrderTotalAmount(),
            'months' => $monthlyTurnover,
        ]);
    }
}
