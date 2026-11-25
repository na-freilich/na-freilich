<?php declare(strict_types=1);

namespace Nf\ProductReviewRequest\ScheduledTask;

use Doctrine\DBAL\Connection;
use Nf\ProductReviewRequest\Checkout\PluginConfig;
use Nf\ProductReviewRequest\Core\Content\ProductReviewRequest\ProductReviewRequestCollection;
use Nf\ProductReviewRequest\Core\Content\ProductReviewRequest\ProductReviewRequestEntity;
use Nf\ProductReviewRequest\Core\Flow\ProductReviewDueEvent;
use Nf\ProductReviewRequest\Service\ProductReviewLinkBuilder;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler(handles: ProductReviewRequestTask::class)]
final class ProductReviewRequestTaskHandler extends ScheduledTaskHandler
{
    private const int BATCH_SIZE = 50;

    /**
     * @param EntityRepository<ScheduledTaskCollection> $scheduledTaskRepository
     * @param EntityRepository<ProductReviewRequestCollection> $followUpRepository
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        private readonly EntityRepository $followUpRepository,
        private readonly Connection $connection,
        private readonly SystemConfigService $systemConfigService,
        private readonly ProductReviewLinkBuilder $productReviewLinkBuilder,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        $context = Context::createCLIContext();
        $criteria = new Criteria();
        $criteria->addFilter(new RangeFilter('sendAt', [
            RangeFilter::LTE => $this->currentTime()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]));
        $criteria->addFilter(new EqualsFilter('triggeredAt', null));
        $criteria->addAssociation('order.orderCustomer');
        $criteria->addAssociation('order.lineItems');
        $criteria->addSorting(new FieldSorting('sendAt', FieldSorting::ASCENDING));
        $criteria->setLimit(self::BATCH_SIZE);

        foreach ($this->followUpRepository->search($criteria, $context) as $followUp) {
            if (!$followUp instanceof ProductReviewRequestEntity) {
                continue;
            }

            $this->dispatchDue($followUp, $context);
        }
    }

    private function dispatchDue(ProductReviewRequestEntity $followUp, Context $context): void
    {
        $order = $followUp->getOrder();
        if (!$order instanceof OrderEntity) {
            $this->exceptionLogger->error('Product review request {followUpId} has no order and was skipped.', [
                'followUpId' => $followUp->getId(),
            ]);
            $this->claim($followUp->getId());

            return;
        }

        if (!$this->systemConfigService->getBool(PluginConfig::ACTIVE, $order->getSalesChannelId())) {
            return;
        }

        $customer = $order->getOrderCustomer();
        $email = $customer?->getEmail() ?? '';
        if ($email === '') {
            $this->exceptionLogger->warning('Product review request for order {orderId} has no customer email.', [
                'orderId' => $order->getId(),
                'followUpId' => $followUp->getId(),
            ]);
            $this->claim($followUp->getId());

            return;
        }

        if (!$this->claim($followUp->getId())) {
            return;
        }

        $name = trim(($customer?->getFirstName() ?? '') . ' ' . ($customer?->getLastName() ?? ''));
        $event = new ProductReviewDueEvent(
            $order,
            $context,
            new MailRecipientStruct([$email => $name !== '' ? $name : $email]),
            $this->productReviewLinkBuilder->build($order),
        );

        try {
            $this->dispatcher->dispatch($event, $event->getName());
        } catch (\Throwable $exception) {
            $this->release($followUp->getId());
            $this->exceptionLogger->error('Could not dispatch the product review request for order {orderId}.', [
                'orderId' => $order->getId(),
                'followUpId' => $followUp->getId(),
                'exception' => $exception,
            ]);
        }
    }

    private function currentTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    private function claim(string $id): bool
    {
        $now = $this->currentTime()->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        return $this->connection->executeStatement(
            'UPDATE nf_product_review_request
             SET triggered_at = :now, updated_at = :now
             WHERE id = :id AND triggered_at IS NULL',
            [
                'now' => $now,
                'id' => Uuid::fromHexToBytes($id),
            ],
        ) === 1;
    }

    private function release(string $id): void
    {
        $this->connection->executeStatement(
            'UPDATE nf_product_review_request
             SET triggered_at = NULL, updated_at = :now
             WHERE id = :id',
            [
                'now' => $this->currentTime()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                'id' => Uuid::fromHexToBytes($id),
            ],
        );
    }
}
