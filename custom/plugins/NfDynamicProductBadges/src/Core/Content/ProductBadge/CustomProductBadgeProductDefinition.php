<?php declare(strict_types=1);

namespace Nf\DynamicProductBadges\Core\Content\ProductBadge;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;

class CustomProductBadgeProductDefinition extends MappingEntityDefinition
{
    public const ENTITY_NAME = 'nf_custom_badge_product';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

//    public function getCollectionClass(): string
//    {
//        return CustomProductBadgeProductCollection::class;
//    }

//    public function getEntityClass(): string
//    {
//        return CustomProductBadgeProductEntity::class;
//    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),

            (new FkField('custom_badge_id', 'customBadgeId', CustomProductBadgeDefinition::class))
                ->addFlags(new Required()),

            (new FkField('product_id', 'productId', \Shopware\Core\Content\Product\ProductDefinition::class))
                ->addFlags(new Required()),

            new ManyToOneAssociationField('customProductBadge', 'custom_badge_id', CustomProductBadgeDefinition::class, 'id', false),
            new ManyToOneAssociationField('product', 'product_id', \Shopware\Core\Content\Product\ProductDefinition::class, 'id', false),
        ]);

    }
}