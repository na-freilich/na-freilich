<?php declare(strict_types=1);

namespace Nf\Booking\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Storefront\Page\Account\Overview\AccountOverviewPageLoadedEvent;
use Nf\Booking\Service\Booking\CreditServiceInterface;
use Shopware\Core\Framework\Struct\ArrayStruct;

readonly class CustomerSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private CreditServiceInterface $creditService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AccountOverviewPageLoadedEvent::class => 'onAccountOverviewLoaded'
        ];
    }

    public function onAccountOverviewLoaded(AccountOverviewPageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        $creditValue = $this->creditService->getCustomerCredit($customer->getId(), $event->getContext());

        $customer->addExtension('nfBookingCredit', new ArrayStruct([
            'value' => $creditValue
        ]));
    }
}