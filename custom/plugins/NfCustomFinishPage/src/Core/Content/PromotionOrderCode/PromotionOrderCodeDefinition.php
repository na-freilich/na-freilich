<?php declare(strict_types=1);

namespace Nf\CustomFinishPage\Core\Content\PromotionOrderCode;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class PromotionOrderCodeDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'nf_promotion_order_code';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),

            new FkField('customer_id', 'customerId', CustomerDefinition::class),
            new FkField('order_id', 'orderId', OrderDefinition::class),

            (new StringField('friend_code', 'friendCode'))->addFlags(new Required()),
            (new StringField('owner_code', 'ownerCode'))->addFlags(new Required()),

            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id', false),
            new ManyToOneAssociationField('order', 'order_id', OrderDefinition::class, 'id', false),
        ]);
    }
}