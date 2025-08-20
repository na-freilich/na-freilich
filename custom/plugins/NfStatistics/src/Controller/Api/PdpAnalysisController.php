<?php

namespace Nf\Statistics\Controller\Api;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

#[Route(defaults: ['_routeScope' => ['api']])]
class PdpAnalysisController extends AbstractController
{
    private $sortFields = [
        'layoutName',
        'views',
        'orders'
    ];

    private $sortDirection = [
        'asc',
        'desc'
    ];

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[Route(path: '/api/_action/nf-statistics/get-pdp-analysis', name: 'api.action.nf-statistics.get-pdp-analysis', methods: ['POST'])]
    public function getProductViews(Request $request, Context $context): Response
    {
        try {
            $page = (int)$request->request->get('page');
            $limit = (int)$request->request->get('limit');
            $languageId = $request->request->get('languageId') == 'null'? $context->getLanguageId() : $request->request->get('languageId');
            $dateFrom = $request->request->get('dateFrom') == ''? null : $request->request->get('dateFrom');
            $dateTo = $request->request->get('dateTo') == ''? null : $request->request->get('dateTo');
            $searchTerm = $request->request->get('searchTerm');
            $sortBy = $request->request->get('sortBy');
            $sortDirection = $request->request->get('sortDirection');

            $page = $page <= 0? 1 : $page;
            $offset = $limit * ($page - 1);

            $whereSql = '';
            $where = [];
            if (!is_null($dateFrom) && !is_null($dateTo))
            {
                $where[] = 'view_date >= "'.$dateFrom.'" and view_date <= "'.$dateTo.'"';
            }
            elseif (!is_null($dateFrom) && is_null($dateTo))
            {
                $where[] = 'view_date >= "'.$dateFrom.'"';
            }
            elseif (is_null($dateFrom) && !is_null($dateTo))
            {
                $where[] = 'view_date <= "'.$dateTo.'"';
            }

            if ($searchTerm)
            {
                $where[] = '(cpt.name like :searchTerm)';
            }

            if (sizeof($where))
            {
                $whereSql = ' where '.implode(' and ', $where);
            }

            if (in_array($sortBy, $this->sortFields) && in_array(strtolower($sortDirection), $this->sortDirection))
            {
                $orderBySql = 'order by '.$sortBy.' '.$sortDirection;
            }

            $sql = '
                select
                    lower(hex(product_layout_id)) as productLayoutId,
                    cpt.name as layoutName,
                    sum(views) as views,
                    sum(order_quantity) as orders
                from nf_stat_product_layout_views as plv
                left join cms_page as cp on
                    plv.product_layout_id = cp.id
                left join cms_page_translation as cpt on 
                    cp.id = cpt.cms_page_id and
                    cpt.cms_page_version_id = cp.version_id and
                    cpt.language_id = unhex(\''.$languageId.'\')
                '.$whereSql.'
                group by plv.product_layout_id
                '.$orderBySql.'
            ';

            $stmt = $this->connection->prepare($sql);
            if ($searchTerm)
            {
                $stmt->bindValue('searchTerm', '%'.$searchTerm.'%');
            }

            $result = $stmt->executeQuery()->fetchAllAssociative();

            return new JsonResponse([
                'status' => 'success',
                'response' => $result,
                'total' => sizeof($result)
            ]);

        } catch (Throwable $e) {
            return $this->statusFailed($e);
        }
    }

    /**
     * @param Throwable $e
     * @return JsonResponse
     */
    private function statusFailed(Throwable $e): JsonResponse
    {
        return new JsonResponse([
            'status' => 'failed',
            'message' => $e->getMessage()
        ]);
    }

}