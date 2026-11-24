<?php declare(strict_types=1);

namespace NfGoogleTagManager\Components;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ListingVariantResolver
{
    public const EXTENSION = 'nfGtmListingItem';

    /**
     * @param SalesChannelRepository<SalesChannelProductEntity> $productRepository
     */
    public function __construct(
        private readonly SalesChannelRepository $productRepository
    ) {
    }

    /**
     * @param iterable<mixed> $products
     */
    public function enrich(iterable $products, SalesChannelContext $context): void
    {
        $parents = [];
        foreach ($products as $product) {
            if (!$product instanceof SalesChannelProductEntity) {
                continue;
            }
            if ($product->getParentId()) {
                continue;
            }
            if (($product->getChildCount() ?? 0) < 1) {
                continue;
            }
            $parents[] = $product;
        }

        if ($parents === []) {
            return;
        }

        $variantIds = [];
        foreach ($parents as $parent) {
            $mainId = $parent->getVariantListingConfig()?->getMainVariantId();
            if (is_string($mainId) && $mainId !== '') {
                $variantIds[$parent->getId()] = $mainId;
                continue;
            }

            $bestId = $this->bestVariantId($parent->getId(), $context);
            if ($bestId !== null && $bestId !== $parent->getId()) {
                $variantIds[$parent->getId()] = $bestId;
            }
        }

        if ($variantIds === []) {
            return;
        }

        $criteria = new Criteria(array_values(array_unique($variantIds)));
        $criteria->addAssociation('options.group');
        $loaded = $this->productRepository->search($criteria, $context)->getEntities();

        foreach ($parents as $parent) {
            $variantId = $variantIds[$parent->getId()] ?? null;
            if ($variantId === null) {
                continue;
            }

            $variant = $loaded->get($variantId);
            if (!$variant instanceof SalesChannelProductEntity) {
                continue;
            }

            $parentNumber = trim((string) $parent->getProductNumber());
            $variantNumber = trim((string) $variant->getProductNumber());
            if ($variantNumber === '') {
                continue;
            }

            $parent->addExtension(self::EXTENSION, new ArrayEntity([
                'item_id' => $variantNumber,
                'item_group_id' => $parentNumber !== '' && $parentNumber !== $variantNumber ? $parentNumber : null,
                'item_variant' => $this->variantLabel($variant->getVariation()),
            ]));
        }
    }

    private function bestVariantId(string $parentId, SalesChannelContext $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', $parentId));
        $criteria->addSorting(new FieldSorting('available', FieldSorting::DESCENDING));
        $criteria->addSorting(new FieldSorting('price'));
        $criteria->setLimit(1);
        $criteria->setTitle('nf-gtm::best-listing-variant');

        return $this->productRepository->searchIds($criteria, $context)->firstId();
    }

    /**
     * @param array<int, mixed> $variation
     */
    private function variantLabel(array $variation): string
    {
        $parts = [];
        foreach ($variation as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $option = trim((string) ($entry['option'] ?? ''));
            if ($option === '') {
                continue;
            }
            $group = trim((string) ($entry['group'] ?? ''));
            $parts[] = $group !== '' ? $group . ': ' . $option : $option;
        }

        return implode(' / ', $parts);
    }
}
