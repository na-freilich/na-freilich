<?php declare(strict_types=1);

namespace Nf\Booking\Core\Content\BookingSeriesDiscount;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;

class NfBookingSeriesDiscountDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'nf_booking_series_discount';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return NfBookingSeriesDiscountEntity::class;
    }

    public function getCollectionClass(): string
    {
        return NfBookingSeriesDiscountCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new IntField('min_count', 'minCount'))->addFlags(new Required()),
            (new FloatField('discount_percentage', 'discountPercentage'))->addFlags(new Required()),
            new BoolField('active', 'active'),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}