<?php

namespace Nf\AdminPlugin\Controller\Api;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route(defaults: ['_routeScope' => ['api']])]
class CustomerStatsController extends AbstractController
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[Route(
        path: '/api/_action/customer-stats/turnover/{customerId}',
        name: 'api.action.customer_stats.turnover',
        methods: ['GET']
    )]
    public function getTurnover(string $customerId): JsonResponse
    {
        $year = (int) date('Y');

        $sql = "
            SELECT 
                MONTH(o.order_date_time) as month,
                SUM(o.amount_total) as turnover,
                MAX(c.order_total_amount) as total_lifetime_amount
            FROM `order` o
            INNER JOIN `order_customer` oc ON o.id = oc.order_id
            INNER JOIN `customer` c ON oc.customer_id = c.id 
            INNER JOIN `state_machine_state` sms ON o.state_id = sms.id
            WHERE oc.customer_id = UNHEX(:customerId)
              AND YEAR(o.order_date_time) = :year
              AND sms.technical_name = 'completed'
            AND o.version_id = :version
            AND oc.version_id = :version
            GROUP BY MONTH(o.order_date_time)
        ";

        $results = $this->connection->fetchAllAssociative($sql, [
            'customerId' => $customerId,
            'year' => $year,
            'version' => hex2bin(Defaults::LIVE_VERSION)
        ]);

        $monthlyTurnover = array_fill(1, 12, 0);

        $totalYear = 0;
        $totalLifetime = 0;

        if (!empty($results)) {
            $totalLifetime = (float) $results[0]['total_lifetime_amount'];

            foreach ($results as $row) {
                $month = (int)$row['month'];
                $value = (float)$row['turnover'];
                $monthlyTurnover[$month] = $value;
                $totalYear += $value;
            }
        }

        $monthlyTurnover = array_filter($monthlyTurnover, function($value) {
            return $value > 0;
        });

        return new JsonResponse([
            'year' => $year,
            'totalYear' => $totalYear,
            'totalLifetime' => $totalLifetime,
            'months' => $monthlyTurnover
        ]);
    }
}