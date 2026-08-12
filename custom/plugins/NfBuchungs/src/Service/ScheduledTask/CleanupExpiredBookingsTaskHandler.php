<?php declare(strict_types=1);

namespace Nf\Booking\Service\ScheduledTask;

use Shopware\Core\Checkout\Cart\CartPersister;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Shopware\Core\Defaults;

#[AsMessageHandler(handles: CleanupExpiredBookingsTask::class)]
class CleanupExpiredBookingsTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        private EntityRepository $bookingRepository,
        private EntityRepository $salesChannelRepository,
        private CartPersister $cartPersister,
        private AbstractSalesChannelContextFactory $contextFactory,
        private LoggerInterface $logger
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        $context = Context::createDefaultContext();
        $salesChannelId = $this->getSalesChannelId($context);

        if (!$salesChannelId) {
            $this->logger->error('Cleanup Task: Active Storefront Sales Channel not found.');
            return;
        }

        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $criteria = new Criteria();
        $criteria->addFilter(new RangeFilter('expiresAt', [RangeFilter::LT => $now]));
        $criteria->addFilter(new EqualsFilter('status', 'pending'));

        $expiredBookings = $this->bookingRepository->search($criteria, $context);

        foreach ($expiredBookings as $booking) {
            $token = $booking->getCartToken();

            $salesChannelContext = $this->contextFactory->create(
                $token,
                $salesChannelId
            );

            try {
                $cart = $this->cartPersister->load($token, $salesChannelContext);

                foreach ($cart->getLineItems() as $lineItem) {
                    if ($lineItem->getReferencedId() === $booking->getProductId()) {
                        $cart->remove($lineItem->getId());
                    }
                }

                $this->cartPersister->save($cart, $salesChannelContext);
                $this->bookingRepository->delete([['id' => $booking->getId()]], $context);

            } catch (\Exception $e) {
                $this->logger->error('Error: ' . $e->getMessage());
            }
        }
    }

    private function getSalesChannelId(Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT));
        $criteria->setLimit(1);

        return $this->salesChannelRepository->searchIds($criteria, $context)->firstId();
    }
}