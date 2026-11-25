<?php declare(strict_types=1);

namespace Nf\ProductReviewRequest\Core\Flow;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\EventData\ArrayType;
use Shopware\Core\Framework\Event\EventData\EntityType;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\ObjectType;
use Shopware\Core\Framework\Event\EventData\ScalarValueType;
use Shopware\Core\Framework\Event\FlowEventAware;
use Shopware\Core\Framework\Event\LanguageAware;
use Shopware\Core\Framework\Event\MailAware;
use Shopware\Core\Framework\Event\MailRecipientStruct;
use Shopware\Core\Framework\Event\OrderAware;
use Shopware\Core\Framework\Event\SalesChannelAware;
use Symfony\Contracts\EventDispatcher\Event;

final class ProductReviewDueEvent extends Event implements FlowEventAware, OrderAware, MailAware, LanguageAware, SalesChannelAware
{
    public const string EVENT_NAME = 'nf_product_review.product_review_due';

    public const string PRODUCT_REVIEW_LINKS = 'productReviewLinks';

    /**
     * @param list<array{label: string, url: string}> $productReviewLinks
     */
    public function __construct(
        private readonly OrderEntity $order,
        private readonly Context $context,
        private readonly MailRecipientStruct $mailRecipientStruct,
        private readonly array $productReviewLinks,
    ) {
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add(OrderAware::ORDER, new EntityType(OrderDefinition::class))
            ->add(self::PRODUCT_REVIEW_LINKS, new ArrayType(
                (new ObjectType())
                    ->add('label', new ScalarValueType(ScalarValueType::TYPE_STRING))
                    ->add('url', new ScalarValueType(ScalarValueType::TYPE_STRING)),
            ));
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function getOrderId(): string
    {
        return $this->order->getId();
    }

    public function getMailStruct(): MailRecipientStruct
    {
        return $this->mailRecipientStruct;
    }

    public function getSalesChannelId(): string
    {
        return $this->order->getSalesChannelId();
    }

    public function getLanguageId(): ?string
    {
        return $this->order->getLanguageId();
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function getProductReviewLinks(): array
    {
        return $this->productReviewLinks;
    }
}
