<?php

namespace Nf\Booking\Core\Content\Booking\Cms;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;

use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Nf\Booking\Core\Content\BookingLocation\NfBookingLocationDefinition;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;

class NfTwoColumnBookingResolver extends AbstractCmsElementResolver
{
    public function getType(): string
    {
        return 'nf-two-column-booking';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $config = $slot->getFieldConfig();
        $criteriaCollection = new CriteriaCollection();

        $leftId = $config->get('locationIdLeft') ? $config->get('locationIdLeft')->getValue() : null;
        if ($leftId) {

            $criteriaCollection->add(
                'location_left_' . $slot->getUniqueIdentifier(),
                NfBookingLocationDefinition::class,
                new Criteria([$leftId]));
        }

        $rightId = $config->get('locationIdRight') ? $config->get('locationIdRight')->getValue() : null;
        if ($rightId) {
            $criteriaCollection->add('location_right_' . $slot->getUniqueIdentifier(),
                NfBookingLocationDefinition::class,
                new Criteria([$rightId]));
        }
//dd($criteriaCollection);
        return \count($criteriaCollection->all()) > 0 ? $criteriaCollection : null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $data = new \Shopware\Core\Framework\Struct\ArrayStruct();
        $slot->setData($data);


        $leftResult = $result->get('location_left_' . $slot->getUniqueIdentifier());
        if ($leftResult && $leftResult->first()) {
            $data->set('locationLeft', $leftResult->first());
        }

        $rightResult = $result->get('location_right_' . $slot->getUniqueIdentifier());
        if ($rightResult && $rightResult->first()) {
            $data->set('locationRight', $rightResult->first());
        }
    }
}