<?php declare(strict_types=1);

namespace Nf\ProductReviewRequest\Service;

use Nf\ProductReviewRequest\Checkout\OrderCustomFields;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

final class OrderConsentService
{
    /**
     * @param EntityRepository<OrderCollection> $orderRepository
     */
    public function __construct(
        private readonly CheckoutConsentReader $checkoutConsentReader,
        private readonly FollowUpScheduleService $followUpScheduleService,
        private readonly EntityRepository $orderRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function storeConsent(CheckoutOrderPlacedEvent $event): void
    {
        $order = $event->getOrder();
        $consent = $this->checkoutConsentReader->read();

        $customFields = $order->getCustomFields() ?? [];
        $customFields[OrderCustomFields::NEWSLETTER_SUBSCRIBED] = $consent->newsletterSubscribed;
        $customFields[OrderCustomFields::REVIEW_ELIGIBLE] = $consent->isReviewEligible();

        try {
            $this->orderRepository->update([[
                'id' => $order->getId(),
                'customFields' => $customFields,
            ]], $event->getContext());
        } catch (\Throwable $exception) {
            $this->logger->error('Could not store product review consent on order {orderId}.', [
                'orderId' => $order->getId(),
                'exception' => $exception,
            ]);

            return;
        }

        $order->setCustomFields($customFields);
        $this->followUpScheduleService->createForPlacedOrder($order, $consent, $event->getContext());
    }
}
