<?php

namespace Nf\Statistics\Controller\Api;

use Doctrine\DBAL\Connection;
use Nf\Statistics\Service\CurrentUserService;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

#[Route(defaults: ['_routeScope' => ['api']])]
class VisitorsController extends AbstractController
{

    private Connection $connection;

    public function __construct(
        private readonly CurrentUserService $currentUserService
    )
    {
    }

    #[Route(path: '/api/_action/nf-statistics/get-visitors-online', name: 'api.action.nf-statistics.get-visitors-online', methods: ['POST'])]
    public function getVisitorsOnline(Request $request, Context $context): Response
    {
        $count = $this->currentUserService->getOnline($context);

        return new JsonResponse([
            'status' => 'success',
            'count' => $count
        ]);
    }

    #[Route(path: '/api/_action/nf-statistics/visitors', name: 'api.action.nf-statistics.visitors', methods: ['POST'])]
    public function getVisitors(Request $request, Context $context): Response
    {
        $total = 0;
        $visitors = $this->currentUserService->getVisitors($request, $context, $total);

        return new JsonResponse([
            'status' => 'success',
            'response' => $visitors,
            'total' => $total
        ]);
    }

    #[Route(path: '/api/_action/nf-statistics/conversion', name: 'api.action.nf-statistics.conversion', methods: ['POST'])]
    public function getConversion(Request $request, Context $context): Response
    {
        $total = 0;
        $visitors = $this->currentUserService->getConversion($request, $context, $total);

        return new JsonResponse([
            'status' => 'success',
            'response' => $visitors,
            'total' => $total
        ]);
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