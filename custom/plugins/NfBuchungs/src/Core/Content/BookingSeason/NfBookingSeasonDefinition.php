<?php

namespace Nf\Booking\Core\Content\BookingSeason;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Nf\Booking\Core\Content\BookingPriceRule\NfBookingPriceRuleDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Nf\Booking\Core\Content\BookingSeason\Aggregate\NfBookingSeasonTranslationDefinition;

class NfBookingSeasonDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'nf_booking_season';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return NfBookingSeasonEntity::class;
    }

    public function getCollectionClass(): string
    {
        return NfBookingSeasonCollection::class;
    }
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new TranslatedField('name'),
            (new StringField('start_date', 'startDate'))->addFlags(new Required()),
            (new StringField('end_date', 'endDate'))->addFlags(new Required()),
            new BoolField('active', 'active'),

            new OneToManyAssociationField('priceRules', NfBookingPriceRuleDefinition::class, 'season_id'),
            new TranslationsAssociationField(
                NfBookingSeasonTranslationDefinition::class,
                'nf_booking_season_id'
            ),
        ]);
    }
}