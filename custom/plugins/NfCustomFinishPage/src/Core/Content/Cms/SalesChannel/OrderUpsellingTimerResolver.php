<?php declare(strict_types=1);

namespace Nf\CustomFinishPage\Core\Content\Cms\SalesChannel;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Nf\CustomFinishPage\Service\DiscountCalculatorTrait;

class OrderUpsellingTimerResolver extends AbstractCmsElementResolver
{
    use DiscountCalculatorTrait;
    public function __construct(
        private readonly EntityRepository $orderRepository,
        private readonly SalesChannelRepository $productRepository,
        private readonly EntityRepository $nfTimerRepository
    )
    {
    }

    public function getType(): string
    {
        return 'order-upselling-timer';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $this->cleanupExpiredTimers($resolverContext->getSalesChannelContext()->getContext());

        $request = $resolverContext->getRequest();
        $orderId = $request->get('orderId')
            ?? $request->attributes->get('orderId')
            ?? $request->query->get('orderId');

        if (!$orderId) {
            return;
        }

        $existingTimer = $this->getExistingTimer((string)$orderId, $resolverContext);

        if ($existingTimer) {
            $upsellProducts = $existingTimer->get('products');
            $endedAt = $existingTimer->get('endedAt');
        } else {
            $data = $this->createNewTimer($orderId, $slot, $resolverContext);
            $upsellProducts = $data->get('products');
            $endedAt = $data->get('endedAt');
        }

        if ($upsellProducts && $upsellProducts->count() > 0) {
            $slot->setData(new ArrayEntity([
                'products' => $upsellProducts,
                'endedAt' => $endedAt
            ]));
        }
    }

    private function getExistingTimer(string $orderId, ResolverContext $resolverContext): ?ArrayEntity
    {
        $context = $resolverContext->getSalesChannelContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));

        $criteria->addAssociation('products');

        /** @var ArrayEntity|null $timer */
        $timer = $this->nfTimerRepository->search($criteria, $context->getContext())->first();

        if (!$timer) {
            return null;
        }

        $baseProducts = $timer->get('products');

        if ($baseProducts) {
            $productIds = $baseProducts->getIds();

            $productCriteria = new Criteria($productIds);
            $productCriteria->addAssociation('cover.media');
            $productCriteria->addAssociation('prices');

            $products = $this->productRepository->search($productCriteria, $context)->getEntities();

            $timer->assign(['products' => $products]);
        }

        return $timer;
    }
    private function createNewTimer(string $orderId, CmsSlotEntity $slot, ResolverContext $resolverContext): ArrayStruct
    {
        $context = $resolverContext->getSalesChannelContext();

        $orderCriteria = new Criteria([$orderId]);
        $orderCriteria->addAssociation('lineItems');
        $orderCriteria->addAssociation('orderCustomer');
        $order = $this->orderRepository->search($orderCriteria, $context->getContext())->first();

        if (!$order) return new ArrayStruct();

        $productIdsInOrder = $order->getLineItems()->fmap(function ($lineItem) {
            return $lineItem->getProductId();
        });
        $config = $slot->getFieldConfig();
        $discountAmount = (float)($config->get('discountAmount') ? $config->get('discountAmount')->getValue() : 10.0);
        $duration = (int) ($config->get('durationMinutes') ? $config->get('durationMinutes')->getValue() : 15);
        $endedAt = (new \DateTime())->add(new \DateInterval("PT{$duration}M"));

        $targetNames = $config->get('crossSellingGroupNames') ? $config->get('crossSellingGroupNames')->getValue() : [];

        $upsellProducts = $this->loadUpsellProducts($productIdsInOrder, $targetNames, $resolverContext);

        if ($upsellProducts->count() > 0) {
            $this->nfTimerRepository->create([[
                'id' => Uuid::randomHex(),
                'orderId' => $order->getId(),
                'customerId' => $order->getOrderCustomer() ? $order->getOrderCustomer()->getCustomerId() : null,
                'discountAmount' => $discountAmount,
                'active' => true,
                'endedAt' => $endedAt->format(\Shopware\Core\Defaults::STORAGE_DATE_TIME_FORMAT),
                'products' => array_map(fn($p) => ['productId' => $p->getId()], $upsellProducts->getElements())
            ]], $context->getContext());
        }

        foreach ($upsellProducts as $product) {
            $this->applyDiscount($product, $discountAmount, $endedAt);
        }

        return new ArrayStruct([
            'products' => $upsellProducts,
            'endedAt' => $endedAt
        ]);
    }

    private function loadUpsellProducts(array $productIds, array $targetNames, ResolverContext $context): ProductCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $productIds));
        $criteria->addAssociation('crossSellings.assignedProducts.product.cover.media');
        $criteria->addAssociation('crossSellings.assignedProducts.product.prices');

        $criteria->getAssociation('crossSellings')
            ->addFilter(new EqualsFilter('active', true));

        $productsInOrder = $this->productRepository->search($criteria, $context->getSalesChannelContext())->getEntities();

        $allUpsellProducts = new ProductCollection();

        foreach ($productsInOrder as $product) {
            $crossSellings = $product->getCrossSellings();
            if (!$crossSellings) continue;

            foreach ($crossSellings as $crossSelling) {
                if (!empty($targetNames) && !in_array($crossSelling->getName(), $targetNames)) {
                    continue;
                }

                $assignedProducts = $crossSelling->getAssignedProducts();
                if (!$assignedProducts) continue;

                foreach ($assignedProducts as $assigned) {
                    $upsellProduct = $assigned->getProduct();

                    if ($upsellProduct && !in_array($upsellProduct->getId(), $productIds)) {
                        $allUpsellProducts->add($upsellProduct);
                    }

                    if ($allUpsellProducts->count() >= 3) {
                        return $allUpsellProducts;
                    }
                }
            }
        }

        return $allUpsellProducts;
    }

    private function cleanupExpiredTimers(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new RangeFilter('endedAt', [
            RangeFilter::LT => (new \DateTime())->format(\Shopware\Core\Defaults::STORAGE_DATE_TIME_FORMAT)
        ]));

        $expiredTimers = $this->nfTimerRepository->search($criteria, $context);

        if ($expiredTimers->count() === 0) {
            return;
        }

        $updatePayload = [];
        foreach ($expiredTimers->getEntities() as $timer) {
            $updatePayload[] = [
                'id' => $timer->getId(),
                'active' => false
            ];
        }

        if (!empty($updatePayload)) {
            $this->nfTimerRepository->update($updatePayload, $context);
        }
    }
}