<?php declare(strict_types=1);

namespace Nf\Booking\Core\Checkout\Customer;

use Nf\Booking\Core\Content\BookingCredit\NfBookingCreditDefinition;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class CustomerExtension extends EntityExtension
{
    public function getEntityName(): string
    {
        return CustomerDefinition::ENTITY_NAME;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToManyAssociationField(
                'nfBookingCredits',
                NfBookingCreditDefinition::class,
                'customer_id'
            )
        );
    }

    public function getDefinitionClass(): string
    {
        return CustomerDefinition::class;
    }
}