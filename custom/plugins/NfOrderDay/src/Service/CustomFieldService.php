<?php declare(strict_types=1);

namespace Nf\OrderDay\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Checkout\Order\OrderDefinition;

/**
 * Class CustomFieldService
 * @package Nf\OrderDay\Service
 */
class CustomFieldService
{
    public const ADDITIONAL_ORDER_DAY_CUSTOM_FIELD_SET = 'nf_order_day';
    public const ORDER_DAYS = 'nf_order_days';

    /**
     * CustomFieldService constructor.
     * @param ContainerInterface $container
     */
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    /**
     * @param Context $context
     * @return void
     */
    public function createAdditionalManufacturerCustomFieldSet(Context $context): void
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $customFieldSetIds = $this->getCustomFieldSetIds(self::ADDITIONAL_ORDER_DAY_CUSTOM_FIELD_SET, $context);

        if (!$customFieldSetIds) {
            $customFieldSetRepository->create([
                [
                    'name' => self::ADDITIONAL_ORDER_DAY_CUSTOM_FIELD_SET,
                    'config' => [
                        'label' => [
                            'de-DE' => 'Bestelltag',
                            'en-GB' => 'Order day'
                        ]
                    ],
                    'position' => 1,
                    'global' => true,
                    'relations' => [
                        ['entityName' => OrderDefinition::ENTITY_NAME],
                    ],
                    'customFields' => [
                        [
                            'name' => self::ORDER_DAYS,
                            'type' => CustomFieldTypes::TEXT,
                            'config' => [
                                'componentName' => 'sw-field',
                                'type' => CustomFieldTypes::TEXT,
                                'customFieldType' => CustomFieldTypes::TEXT,
                                'customFieldPosition' => 1,
                                'label' => [
                                    'de-DE' => 'Tage',
                                    'en-GB' => 'Days'
                                ],
                                'placeholder' => [
                                    'de-DE' => 'Text eingeben...',
                                    'en-GB' => 'Enter the text...'
                                ]
                            ]
                        ]
                    ]
                ]
            ], $context);
        }
    }

    /**
     * @param string $customFieldSetName
     * @param Context $context
     * @return IdSearchResult|null
     */
    private function getCustomFieldSetIds(string $customFieldSetName, Context $context): ?IdSearchResult
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('name', [$customFieldSetName]));

        $customFieldSetIds = $customFieldSetRepository->searchIds($criteria, $context);

        return $customFieldSetIds->getTotal() > 0 ? $customFieldSetIds : null;
    }

    /**
     * @param string $customFieldSetName
     * @param Context $context
     * @return void
     */
    public function deleteCustomFieldSet(string $customFieldSetName, Context $context): void
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $customFieldSetIds = $this->getCustomFieldSetIds($customFieldSetName, $context);

        if ($customFieldSetIds) {
            $customFieldSetRepository->delete(array_values($customFieldSetIds->getData()), $context);
        }
    }
}