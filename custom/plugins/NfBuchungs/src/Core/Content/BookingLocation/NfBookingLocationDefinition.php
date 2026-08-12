<?php declare(strict_types=1);

namespace Nf\Booking\Core\Content\BookingLocation;

use Nf\Booking\Core\Content\BookingLocation\Aggregate\NfBookingLocationTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class NfBookingLocationDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'nf_booking_location';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return NfBookingLocationEntity::class;
    }

    public function getCollectionClass(): string
    {
        return NfBookingLocationCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new TranslatedField('name'),
            new BoolField('active', 'active'),

            new TranslationsAssociationField(
                NfBookingLocationTranslationDefinition::class,
                'nf_booking_location_id'
            ),
        ]);
    }
}