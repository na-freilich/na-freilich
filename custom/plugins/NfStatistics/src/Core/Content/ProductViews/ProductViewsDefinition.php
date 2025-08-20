<?php declare(strict_types=1);

namespace Nf\Statistics\Core\Content\ProductViews;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductViewsDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'nf_stat_product_views';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return StatCurrentUsersEntity::class;
    }

    public function getCollectionClass(): string
    {
        return StatCurrentUsersCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new DateField('view_date', 'viewDate'))->addFlags(new Required()),
            (new IntField('views', 'views')),
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new Required()),
            (new OneToOneAssociationField('product', 'product_id', 'id', ProductDefinition::class, true))->addFlags(new CascadeDelete()),
        ]);
    }
}