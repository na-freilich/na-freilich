<?php declare(strict_types=1);

namespace Nf\DynamicProductBadges\Core\Content\Product\Extension;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Nf\DynamicProductBadges\Core\Content\ProductBadge\CustomProductBadgeDefinition;
use Nf\DynamicProductBadges\Core\Content\ProductBadge\CustomProductBadgeProductDefinition;
class ProductCustomBadgeExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new ManyToManyAssociationField(
                'nfBadges',
                CustomProductBadgeDefinition::class,
                CustomProductBadgeProductDefinition::class, // mapping entity
                'product_id',                             // mapping column for product
                'custom_badge_id'                      // mapping column for badge
            )
        );
    }

    public function getEntityName(): string
    {
        return ProductDefinition::ENTITY_NAME;
    }
}