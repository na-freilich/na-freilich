<?php declare(strict_types=1);

namespace Nf\CustomFinishPage\Core\Content\NfUpsellingTimer;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Content\Product\ProductDefinition;

class NfUpsellingTimerDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'nf_upselling_order_timer';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),

            (new FkField('order_id', 'orderId', \Shopware\Core\Checkout\Order\OrderDefinition::class))->addFlags(new Required()),
            (new FkField('customer_id', 'customerId', \Shopware\Core\Checkout\Customer\CustomerDefinition::class)),

            (new FloatField('discount_amount', 'discountAmount'))->addFlags(new Required()),
            new BoolField('active', 'active'),

            (new DateTimeField('ended_at', 'endedAt'))->addFlags(new Required()),

            new ManyToManyAssociationField(
                'products',
                ProductDefinition::class,
                NfUpsellingTimerProductDefinition::class,
                'upselling_order_timer_id',
                'product_id'
            ),
        ]);
    }
}