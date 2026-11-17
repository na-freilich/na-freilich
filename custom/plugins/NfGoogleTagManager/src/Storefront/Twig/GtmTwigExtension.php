<?php declare(strict_types=1);

namespace NfGoogleTagManager\Storefront\Twig;

use NfGoogleTagManager\Components\DataLayerMapper;
use NfGoogleTagManager\Components\ItemPropertyMap;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GtmTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly DataLayerMapper $mapper,
        private readonly ItemPropertyMap $itemPropertyMap
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('nf_gtm_item', $this->mapItem(...)),
        ];
    }

    /**
     * @param array{item_list_id?: string, item_list_name?: string, item_category?: string, index?: int} $listContext
     *
     * @return array<string, mixed>
     */
    public function mapItem(SalesChannelProductEntity $product, SalesChannelContext $context, array $listContext = []): array
    {
        return $this->mapper->mapProductItem(
            $product,
            $context,
            1,
            $listContext,
            $this->itemPropertyMap->forSalesChannel($context->getSalesChannelId())
        );
    }
}
