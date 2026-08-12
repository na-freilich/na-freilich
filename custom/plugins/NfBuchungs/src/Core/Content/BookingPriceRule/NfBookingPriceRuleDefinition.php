<?php

namespace Nf\Booking\Core\Content\BookingPriceRule;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Nf\Booking\Core\Content\BookingSeason\NfBookingSeasonDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;

class NfBookingPriceRuleDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'nf_booking_price_rule';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('season_id', 'seasonId', NfBookingSeasonDefinition::class))->addFlags(new Required()),

            (new JsonField('days', 'days'))->addFlags(new Required()), // [1,2,3,4,5]
            (new StringField('start_time', 'startTime'))->addFlags(new Required()),
            (new StringField('end_time', 'endTime'))->addFlags(new Required()),
            (new FloatField('price', 'price'))->addFlags(new Required()),
            (new FloatField('price_subsequent', 'priceSubsequent'))->addFlags(new Required()),

            new ManyToOneAssociationField('season', 'season_id', NfBookingSeasonDefinition::class, 'id', false)
        ]);
    }
}