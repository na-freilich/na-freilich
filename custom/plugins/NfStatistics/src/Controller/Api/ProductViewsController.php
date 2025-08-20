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
class ProductViewsController extends AbstractController
{
    private $sortFields = [
        'productNumber',
        'views',
        'name'
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

    #[Route(path: '/api/_action/nf-statistics/get-product-views', name: 'api.action.nf-statistics.get-product-views', methods: ['POST'])]
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
                $where[] = '(p.product_number like :searchTerm or pt.name like :searchTerm or ppt.name like :searchTerm)';
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
                    hex(nspv.product_id) as productId,
                    product_number as productNumber,
                    sum(views) as views,
                    if (isnull(pt.name), ppt.name, pt.name) as name
                from nf_stat_product_views as nspv
                left join product as p on p.id = nspv.product_id
                left join product_translation as pt on 
                    p.id = pt.product_id and 
                    pt.product_version_id = p.version_id and
                    pt.language_id = unhex(\''.$languageId.'\')
                left join product_translation as ppt on 
                    p.parent_id = ppt.product_id and 
                    ppt.product_version_id = p.parent_version_id and
                    ppt.language_id = unhex(\''.$languageId.'\')
                '.$whereSql.'
                group by nspv.product_id
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