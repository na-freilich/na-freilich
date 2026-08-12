<?php declare(strict_types=1);

namespace Nf\Booking\Core\Content\BookingCredit;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;

class NfBookingCreditDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'nf_booking_credit';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return NfBookingCreditEntity::class;
    }

    public function getCollectionClass(): string
    {
        return NfBookingCreditCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FloatField('total_slots', 'totalSlots'))->addFlags(new Required()),
            (new FloatField('used_slots', 'usedSlots')),
            new BoolField('active', 'active'),
            new JsonField('history', 'history'),
            new FkField('customer_id', 'customerId', CustomerDefinition::class),
            new LongTextField('comment', 'comment'),
            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id', false),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}