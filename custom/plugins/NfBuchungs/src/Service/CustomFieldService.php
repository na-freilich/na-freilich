<?php declare(strict_types=1);

namespace Nf\Booking\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Content\Product\ProductDefinition;

/**
 * Class CustomFieldService
 * @package Nf\Booking\Service
 */
class CustomFieldService
{
    public const BOOKING_PRODUCT_SET = 'nf_booking_product_settings';
    public const FIELD_MIN_DURATION = 'min_booking_duration';
    public const FIELD_BOOKING_STEP = 'booking_step';

    /**
     * CustomFieldService constructor.
     * @param ContainerInterface $container
     */
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public function createAdditionalCustomFields(Context $context): void
    {
        $this->createProductBookingCustomFields($context);
    }

    private function createProductBookingCustomFields(Context $context): void
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        if ($this->getCustomFieldSetIds(self::BOOKING_PRODUCT_SET, $context)) {
            return;
        }

        $customFieldSetRepository->create([
            [
                'name' => self::BOOKING_PRODUCT_SET,
                'config' => [
                    'label' => [
                        'de-DE' => 'Buchungseinstellungen',
                        'en-GB' => 'Booking Settings'
                    ]
                ],
                'position' => 2,
                'relations' => [
                    ['entityName' => ProductDefinition::ENTITY_NAME],
                ],
                'customFields' => [
                    [
                        'name' => self::FIELD_MIN_DURATION,
                        'type' => CustomFieldTypes::SELECT,
                        'config' => [
                            'componentName' => 'sw-single-select',
                            'customFieldType' => CustomFieldTypes::SELECT,
                            'customFieldPosition' => 1,
                            'label' => [
                                'de-DE' => 'Mindestbuchungsdauer',
                                'en-GB' => 'Min. booking duration'
                            ],
                            'options' => [
//                                ['value' => '30', 'label' => ['de-DE' => '30 Min', 'en-GB' => '30 Min']],
                                ['value' => '60', 'label' => ['de-DE' => '1 Stunde', 'en-GB' => '1 Hour']],
                                ['value' => '90', 'label' => ['de-DE' => '1,5 Stunden', 'en-GB' => '1.5 Hours']],
                                ['value' => '120', 'label' => ['de-DE' => '2 Stunden', 'en-GB' => '2 Hours']],
                            ]
                        ]
                    ],
                    [
                        'name' => self::FIELD_BOOKING_STEP,
                        'type' => CustomFieldTypes::SELECT,
                        'config' => [
                            'componentName' => 'sw-single-select',
                            'customFieldType' => CustomFieldTypes::SELECT,
                            'customFieldPosition' => 2,
                            'label' => [
                                'de-DE' => 'Buchungsschritt / Intervall',
                                'en-GB' => 'Booking step / Interval'
                            ],
                            'options' => [
                                ['value' => '30', 'label' => ['de-DE' => '30 Min', 'en-GB' => '30 Min']],
                                ['value' => '60', 'label' => ['de-DE' => '60 Min', 'en-GB' => '60 Min']],
                            ]
                        ]
                    ]
                ]
            ]
        ], $context);
    }

    private function getCustomFieldSetIds(string $customFieldSetName, Context $context): ?IdSearchResult
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('name', [$customFieldSetName]));
        $customFieldSetIds = $customFieldSetRepository->searchIds($criteria, $context);

        return $customFieldSetIds->getTotal() > 0 ? $customFieldSetIds : null;
    }

    public function deleteCustomFieldSet(string $customFieldSetName, Context $context): void
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $customFieldSetIds = $this->getCustomFieldSetIds($customFieldSetName, $context);

        if ($customFieldSetIds) {
            $customFieldSetRepository->delete(array_values($customFieldSetIds->getData()), $context);
        }
    }
}