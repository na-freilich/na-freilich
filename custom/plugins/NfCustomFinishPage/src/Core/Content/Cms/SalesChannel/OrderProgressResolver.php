<?php declare(strict_types=1);

namespace Nf\CustomFinishPage\Core\Content\Cms\SalesChannel;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class OrderProgressResolver
{
    public function __construct(
        private readonly EntityRepository $orderRepository)
    {
    }

    public function getType(): string
    {
        return 'order-progress';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?ElementDataCollection
    {
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $data = new ArrayStruct(['currentStep' => 1]);
        $request = $resolverContext->getRequest();

        $orderId = $request->get('orderId')
            ?? $request->attributes->get('orderId')
            ?? $request->query->get('orderId');

        if (!$orderId) {
            $params = $request->attributes->get('_route_params');
            $orderId = $params['orderId'] ?? null;
        }

        if ($orderId) {
            $criteria = new Criteria([$orderId]);
            $criteria->addAssociation('stateMachineState');
            $criteria->addAssociation('deliveries.stateMachineState');

            $order = $this->orderRepository->search($criteria, $resolverContext->getSalesChannelContext()->getContext())->first();

            if ($order instanceof OrderEntity) {
                $orderState = $order->getStateMachineState()->getTechnicalName();

                $delivery = $order->getDeliveries() ? $order->getDeliveries()->first() : null;
                $deliveryState = $delivery && $delivery->getStateMachineState()
                    ? $delivery->getStateMachineState()->getTechnicalName()
                    : 'open';

                $step = 1;

                if ($orderState === 'in_progress') {
                    $step = 2;
                }

                if (in_array($deliveryState, ['shipped', 'shipped_partially', 'returned'])) {
                    $step = 3;
                }

                if ($orderState === 'completed') {
                    $step = 4;
                }

                $data->set('currentStep', $step);
            }
        }

        $slot->setData($data);
    }
}