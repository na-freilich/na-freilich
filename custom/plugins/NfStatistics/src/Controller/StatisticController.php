<?php declare(strict_types=1);

namespace Nf\Statistics\Controller;

use Nf\Statistics\Service\CurrentUserService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class StatisticController extends StorefrontController
{
    public function __construct(
        private readonly CurrentUserService $currentUserService
    )
    {
    }

    #[Route(path: '/nf-statistics/update', name: 'nf-statistics.update', methods: ['POST'], defaults: ["XmlHttpRequest" => true])]
    public function updateStatistics(Request $request, SalesChannelContext $context): Response
    {
        $this->currentUserService->updateStatistic($context, $request);

        return new JsonResponse([
            'status' => 'success'
        ]);
    }
}