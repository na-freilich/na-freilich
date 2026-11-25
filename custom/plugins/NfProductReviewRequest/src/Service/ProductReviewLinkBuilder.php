<?php declare(strict_types=1);

namespace Nf\ProductReviewRequest\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

final class ProductReviewLinkBuilder
{
    /**
     * @param EntityRepository<OrderLineItemCollection> $lineItemRepository
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EntityRepository $lineItemRepository,
    ) {
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function build(OrderEntity $order): array
    {
        $lineItems = $order->getLineItems();
        if ($lineItems === null || $lineItems->count() === 0) {
            $criteria = (new Criteria())->addFilter(new EqualsFilter('orderId', $order->getId()));
            $loaded = $this->lineItemRepository->search($criteria, Context::createCLIContext())->getEntities();
            $lineItems = $loaded instanceof OrderLineItemCollection ? $loaded : new OrderLineItemCollection();
        }

        $domain = $this->domainUrl($order->getSalesChannelId(), $order->getLanguageId());
        $links = [];

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }

            $productId = $lineItem->getReferencedId();
            if ($productId === null || $productId === '') {
                continue;
            }

            $links[] = [
                'label' => $lineItem->getLabel() ?? '',
                'url' => $this->reviewUrl($domain, $order->getSalesChannelId(), $order->getLanguageId(), $productId),
            ];
        }

        return $links;
    }

    private function reviewUrl(?string $domain, string $salesChannelId, ?string $languageId, string $productId): string
    {
        $path = $this->seoPath($salesChannelId, $languageId, $productId) ?? ('detail/' . $productId);
        $base = $domain !== null ? rtrim($domain, '/') . '/' : '/';

        return $base . ltrim($path, '/') . '#review-tab-' . $productId . '-pane';
    }

    private function domainUrl(string $salesChannelId, ?string $languageId): ?string
    {
        $salesChannelId = Uuid::fromHexToBytes($salesChannelId);

        if ($languageId !== null && $languageId !== '') {
            $url = $this->connection->fetchOne(
                'SELECT url FROM sales_channel_domain
                 WHERE sales_channel_id = :salesChannelId AND language_id = :languageId
                 ORDER BY url ASC
                 LIMIT 1',
                [
                    'salesChannelId' => $salesChannelId,
                    'languageId' => Uuid::fromHexToBytes($languageId),
                ],
            );

            if (\is_string($url) && $url !== '') {
                return $url;
            }
        }

        $url = $this->connection->fetchOne(
            'SELECT url FROM sales_channel_domain
             WHERE sales_channel_id = :salesChannelId
             ORDER BY url ASC
             LIMIT 1',
            ['salesChannelId' => $salesChannelId],
        );

        return \is_string($url) && $url !== '' ? $url : null;
    }

    private function seoPath(string $salesChannelId, ?string $languageId, string $productId): ?string
    {
        $sql = 'SELECT seo_path_info FROM seo_url
                WHERE sales_channel_id = :salesChannelId
                  AND route_name = :route
                  AND foreign_key = :productId
                  AND is_canonical = 1
                  AND is_deleted = 0';
        $params = [
            'salesChannelId' => Uuid::fromHexToBytes($salesChannelId),
            'route' => 'frontend.detail.page',
            'productId' => Uuid::fromHexToBytes($productId),
        ];

        if ($languageId !== null && $languageId !== '') {
            $sql .= ' AND language_id = :languageId';
            $params['languageId'] = Uuid::fromHexToBytes($languageId);
        }

        $sql .= ' LIMIT 1';
        $path = $this->connection->fetchOne($sql, $params);

        return \is_string($path) && $path !== '' ? $path : null;
    }
}
