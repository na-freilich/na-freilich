<?php declare(strict_types=1);

namespace Nf\NachlassFilter\Core\Content\Product\Cms;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\CmsElementResolverInterface;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\Log\Package;

#[Package('inventory')]
class NachlassProductListingCmsElementResolver extends AbstractCmsElementResolver
{
    const filterName = 'nachlass-filter';
    /**
     * @internal
     */
    public function __construct(
        private readonly CmsElementResolverInterface $originalCmsElementResolver
    )
    {
    }

    public function getType(): string
    {
        return 'product-listing';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $config = $slot->get('config');
        if (isset($config['filters']['value']) && $config['filters']['value']) {
            // apply config settings
            $config = explode(',', (string) $config['filters']['value']);

            $request = $resolverContext->getRequest();

            if (\in_array(self::filterName, $config)) {
                $request->request->set(self::filterName, true);
            }
        }

        $this->originalCmsElementResolver->enrich($slot, $resolverContext, $result);
    }
}