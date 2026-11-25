<?php declare(strict_types=1);

namespace Nf\ProductReviewRequest\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Nf\ProductReviewRequest\Checkout\CheckoutConsent;
use Nf\ProductReviewRequest\Checkout\PluginConfig;
use Nf\ProductReviewRequest\Core\Content\ProductReviewRequest\ProductReviewRequestCollection;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;

final class FollowUpScheduleService
{
    /**
     * @param EntityRepository<ProductReviewRequestCollection> $followUpRepository
     * @param EntityRepository<OrderDeliveryCollection> $deliveryRepository
     */
    public function __construct(
        private readonly EntityRepository $followUpRepository,
        private readonly EntityRepository $deliveryRepository,
        private readonly SystemConfigService $systemConfigService,
        private readonly Connection $connection,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function createForPlacedOrder(OrderEntity $order, CheckoutConsent $consent, Context $context): void
    {
        if (!$consent->isReviewEligible()) {
            return;
        }

        $versionId = $order->getVersionId() ?? Defaults::LIVE_VERSION;
        $payload = [
            'id' => Uuid::randomHex(),
            'orderId' => $order->getId(),
            'orderVersionId' => $versionId,
        ];

        if ($this->deliveryIsShipped($order, $context)) {
            $payload['sendAt'] = $this->dueAt($order->getSalesChannelId());
        }

        try {
            $this->followUpRepository->create([$payload], $context);
        } catch (\Throwable $exception) {
            if ($this->isDuplicate($exception)) {
                return;
            }

            $this->logger->error('Could not store the product review request for order {orderId}.', [
                'orderId' => $order->getId(),
                'exception' => $exception,
            ]);
        }
    }

    public function onDeliveryTransition(StateMachineTransitionEvent $event): void
    {
        if ($event->getEntityName() !== OrderDeliveryDefinition::ENTITY_NAME) {
            return;
        }

        if ($event->getToPlace()->getTechnicalName() !== PluginConfig::SHIPPED_STATE) {
            return;
        }

        $criteria = new Criteria([$event->getEntityId()]);
        $criteria->addAssociation('order');
        $delivery = $this->deliveryRepository->search($criteria, $event->getContext())->first();

        if (!$delivery instanceof OrderDeliveryEntity || $delivery->getOrderId() === '') {
            return;
        }

        $dueAt = $this->dueAt($delivery->getOrder()?->getSalesChannelId());
        $this->connection->executeStatement(
            'UPDATE nf_product_review_request
             SET send_at = :sendAt, updated_at = :updatedAt
             WHERE order_id = :orderId
               AND order_version_id = :orderVersionId
               AND send_at IS NULL',
            [
                'sendAt' => $dueAt->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                'updatedAt' => $dueAt->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                'orderId' => Uuid::fromHexToBytes($delivery->getOrderId()),
                'orderVersionId' => Uuid::fromHexToBytes($delivery->getOrderVersionId()),
            ],
        );
    }

    private function deliveryIsShipped(OrderEntity $order, Context $context): bool
    {
        $deliveries = $order->getDeliveries();
        if ($deliveries !== null) {
            $sawState = false;
            foreach ($deliveries as $delivery) {
                $state = $delivery->getStateMachineState();
                if ($state === null) {
                    continue;
                }

                $sawState = true;
                if ($state->getTechnicalName() === PluginConfig::SHIPPED_STATE) {
                    return true;
                }
            }

            if ($sawState) {
                return false;
            }
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $order->getId()));
        $criteria->addAssociation('stateMachineState');

        foreach ($this->deliveryRepository->search($criteria, $context) as $delivery) {
            if ($delivery->getStateMachineState()?->getTechnicalName() === PluginConfig::SHIPPED_STATE) {
                return true;
            }
        }

        return false;
    }

    private function dueAt(?string $salesChannelId): \DateTimeImmutable
    {
        $configured = $this->systemConfigService->get(PluginConfig::DELAY_DAYS, $salesChannelId);
        $days = \is_numeric($configured) ? max(0, (int) $configured) : 7;

        return $this->clock->now()->modify(\sprintf('+%d days', $days));
    }

    private function isDuplicate(\Throwable $exception): bool
    {
        $current = $exception;
        while ($current !== null) {
            if ($current instanceof UniqueConstraintViolationException) {
                return true;
            }

            $current = $current->getPrevious();
        }

        return false;
    }
}
