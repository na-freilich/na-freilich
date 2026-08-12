<?php

namespace Nf\Booking\Core\Content\BookingLocation\Aggregate;

use Nf\Booking\Core\Content\BookingLocation\NfBookingLocationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class NfBookingLocationTranslationDefinition extends EntityTranslationDefinition
{

    public const ENTITY_NAME = 'nf_booking_location_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getParentDefinitionClass(): string
    {
        return NfBookingLocationDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('name', 'name'))->addFlags(new Required()),
        ]);
    }
}