<?php declare(strict_types=1);

namespace Nf\CustomFinishPage\Core\Content\NfUpsellingTimer;

use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Content\Product\ProductDefinition;

class NfUpsellingTimerProductDefinition extends MappingEntityDefinition
{
    public const ENTITY_NAME = 'nf_upselling_order_timer_product';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('upselling_order_timer_id', 'upsellingOrderTimerId', NfUpsellingTimerDefinition::class))->addFlags(new PrimaryKey(), new Required()),

            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new PrimaryKey(), new Required()),
        ]);
    }
}