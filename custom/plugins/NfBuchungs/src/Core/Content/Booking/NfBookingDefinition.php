<?php declare(strict_types=1);

namespace Nf\Booking\Core\Content\Booking;

use Nf\Booking\Core\Content\Booking\BookingItem\NfBookingItemDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Nf\Booking\Core\Content\BookingSeriesDiscount\NfBookingSeriesDiscountDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;

class NfBookingDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'nf_booking';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return NfBookingEntity::class;
    }

    public function getCollectionClass(): string
    {
        return NfBookingCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new FkField('product_id',
                'productId', ProductDefinition::class),
            new FkField('order_id', 'orderId', OrderDefinition::class),
            new StringField('cart_token', 'cartToken'),
            (new DateTimeField('expires_at', 'expiresAt')),
            (new StringField('order_number', 'orderNumber'))->addFlags(new Required()),
            (new FloatField('total_price', 'totalPrice'))->addFlags(new Required()),
            (new StringField('status', 'status'))->addFlags(new Required()),
            new FloatField('original_price', 'originalPrice'),
            new FloatField('total_credit_slots', 'totalCreditSlots'),
            new FloatField('total_credit_amount', 'totalCreditAmount'),
            new JsonField('credit_info', 'creditInfo'),
            new FloatField('discount_amount', 'discountAmount'),
            new FkField('discount_id', 'discountId', NfBookingSeriesDiscountDefinition::class),
            new FkField('customer_id', 'customerId', CustomerDefinition::class),
            new ManyToOneAssociationField('discount', 'discount_id', NfBookingSeriesDiscountDefinition::class, 'id', false),
            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id', false),
            (new OneToManyAssociationField('items', NfBookingItemDefinition::class, 'booking_id'))->addFlags(new CascadeDelete()),
            (new ManyToOneAssociationField(
                'product',
                'product_id',
                ProductDefinition::class,
                'id',
                false
            ))->addFlags(new ApiAware()),
        ]);
    }
}