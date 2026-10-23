<?php declare(strict_types=1);

namespace Nf\AdminPlugin;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;


class AdminPlugin extends Plugin
{
    public const ADDITIONAL_MANUFACTURER_DATA_CUSTOM_FIELD_SET = 'nf_manufacturer';
    public const MANUFACTURER_DATA_CUSTOM_FIELD = 'nf_manufacturer_gpsr';
    public const ADDITIONAL_NEWSLETTER_DATA_CUSTOM_FIELD_SET = 'nf_newsletter';
    public const NEWSLETTER_DATA_CUSTOM_FIELD = 'nf_newsletter_birthday';

    public function activate(ActivateContext $activateContext): void
    {
        parent::install($activateContext);

        $this->createAdditionalDataCustomFieldSet($activateContext->getContext());
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::install($updateContext);

        $this->createAdditionalDataCustomFieldSet($updateContext->getContext());
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);

        $this->deleteCustomFieldSet(self::ADDITIONAL_MANUFACTURER_DATA_CUSTOM_FIELD_SET, $deactivateContext->getContext());
    }

    /**
     * @param Context $context
     * @return void
     */
    private function createAdditionalDataCustomFieldSet(Context $context): void
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $customFieldSetIds = $this->getCustomFieldSetIds(self::ADDITIONAL_MANUFACTURER_DATA_CUSTOM_FIELD_SET, $context);

        if (!$customFieldSetIds)
        {
            $customFieldSetRepository->upsert([
                [
                    'name' => 'nf_manufacturer',
                    'config' => [
                        'label' => [
                            'de-DE' => 'Herstellerdaten GPSR',
                            'en-GB' => 'manufacturer data GPSR'
                        ]
                    ],
                    'relations' => [[
                        'entityName' => 'product_manufacturer'
                    ]],
                    'customFields' => [
                        [
                            'name' => self::MANUFACTURER_DATA_CUSTOM_FIELD,
                            'type' => CustomFieldTypes::HTML,
                            'config' => [
                                'componentName' => 'sw-text-editor',
                                'customFieldType' => 'textEditor',
                                'customFieldPosition' => 1,
                                'label' => [
                                    'de-DE' => 'Herstellerdaten GPSR',
                                    'en-GB' => 'manufacturer data GPSR'
                                ]
                            ]
                        ]
                    ]
                ]
            ], $context);
        }

        $newsletterCustomFieldSetIds = $this->getCustomFieldSetIds(self::ADDITIONAL_NEWSLETTER_DATA_CUSTOM_FIELD_SET, $context);

        if (!$newsletterCustomFieldSetIds)
        {
            $customFieldSetRepository->upsert([
                [
                    'name' => self::ADDITIONAL_NEWSLETTER_DATA_CUSTOM_FIELD_SET,
                    'config' => [
                        'label' => [
                            'de-DE' => 'Newsletter zusätzliche Daten',
                            'en-GB' => 'Newsletter additional data'
                        ]
                    ],
                    'relations' => [[
                        'entityName' => 'newsletter_recipient'
                    ]],
                    'customFields' => [
                        [
                            'name' => self::NEWSLETTER_DATA_CUSTOM_FIELD,
                            'type' => CustomFieldTypes::DATETIME,
                            'config' => [
                                'componentName' => 'sw-field',
                                'customFieldType' => 'date',
                                'customFieldPosition' => 1,
                                'label' => [
                                    'de-DE' => 'Birthday',
                                    'en-GB' => 'Geburtstag'
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

        $newsletterCustomFieldSetIds = $this->getCustomFieldSetIds(self::ADDITIONAL_NEWSLETTER_DATA_CUSTOM_FIELD_SET, $context);
        if ($newsletterCustomFieldSetIds) {
            $customFieldSetRepository->delete(array_values($newsletterCustomFieldSetIds->getData()), $context);
        }
    }
}
