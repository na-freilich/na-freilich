<?php declare(strict_types=1);

namespace Nf\DynamicProductBadges\Core\Content\ProductBadge;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class CustomProductBadgeDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'nf_custom_badge';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return CustomProductBadgeCollection::class;
    }

    public function getEntityClass(): string
    {
        return CustomProductBadgeEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey(), new ApiAware()),
            (new StringField('name', 'name'))->addFlags(new Required(), new ApiAware()),
            (new StringField('text', 'text'))->addFlags(new Required(), new ApiAware()),
            (new StringField('color', 'color'))->addFlags(new ApiAware()),
        ]);
    }
}