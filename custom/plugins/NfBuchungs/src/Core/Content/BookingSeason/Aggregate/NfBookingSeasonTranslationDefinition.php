<?php

namespace Nf\Booking\Core\Content\BookingSeason\Aggregate;

use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Nf\Booking\Core\Content\BookingSeason\NfBookingSeasonDefinition;

class NfBookingSeasonTranslationDefinition extends EntityTranslationDefinition
{
    public const ENTITY_NAME = 'nf_booking_season_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getParentDefinitionClass(): string
    {
        return NfBookingSeasonDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('name', 'name'))->addFlags(new Required()),
        ]);
    }
}