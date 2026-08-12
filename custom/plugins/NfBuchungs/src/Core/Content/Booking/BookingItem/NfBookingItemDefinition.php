<?php

namespace Nf\Booking\Core\Content\Booking\BookingItem;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Nf\Booking\Core\Content\Booking\NfBookingDefinition;
use Nf\Booking\Core\Content\BookingLocation\NfBookingLocationDefinition;

class NfBookingItemDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'nf_booking_item';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return NfBookingItemEntity::class;
    }

    public function getCollectionClass(): string
    {
        return NfBookingItemCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('booking_id', 'bookingId', NfBookingDefinition::class))->addFlags(new Required()),
            new ManyToOneAssociationField('booking', 'booking_id', NfBookingDefinition::class, 'id', false),
            (new FkField('location_id', 'locationId', NfBookingLocationDefinition::class))->addFlags(new Required()),
            new ManyToOneAssociationField('location', 'location_id', NfBookingLocationDefinition::class, 'id', false),
            (new DateField('booking_date', 'bookingDate'))->addFlags(new Required()),
            (new StringField('start_time', 'startTime'))->addFlags(new Required()),
            (new StringField('end_time', 'endTime'))->addFlags(new Required()),
            (new FloatField('unit_price', 'unitPrice'))->addFlags(new Required()),
        ]);
    }
}