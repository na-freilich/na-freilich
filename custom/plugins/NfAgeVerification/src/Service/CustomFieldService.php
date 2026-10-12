<?php declare(strict_types=1);

namespace NfAgeVerification\Service;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class CustomFieldService
{
    public const AGE_VERIFICATION_CUSTOM_FIELD_SET = 'nf_age_verification';
    public const AGE_RESTRICTION_ACTIVE = 'age_restriction_active';

    public function __construct(
        private readonly EntityRepository $customFieldSetRepository
    ) {}

    public function createCustomFieldSet(Context $context): void
    {
        $customFieldSetIds = $this->getCustomFieldSetIds(self::AGE_VERIFICATION_CUSTOM_FIELD_SET, $context);

        if (!$customFieldSetIds) {
            $this->customFieldSetRepository->create([
                [
                    'name' => self::AGE_VERIFICATION_CUSTOM_FIELD_SET,
                    'config' => [
                        'label' => [
                            'de-DE' => 'Altersprüfung',
                            'en-GB' => 'Age Verification',
                            'ru-RU' => 'Проверка возраста',
                        ],
                    ],
                    'position' => 1,
                    'global' => true,
                    'relations' => [
                        ['entityName' => ProductDefinition::ENTITY_NAME],
                    ],
                    'customFields' => [
                        [
                            'name' => self::AGE_RESTRICTION_ACTIVE,
                            'type' => CustomFieldTypes::BOOL,
                            'config' => [
                                'componentName' => 'sw-field',
                                'type' => 'checkbox',
                                'customFieldType' => CustomFieldTypes::BOOL,
                                'customFieldPosition' => 1,
                                'label' => [
                                    'de-DE' => 'Altersbeschränkung aktivieren',
                                    'en-GB' => 'Enable age restriction',
                                ],
                            ],
                        ],
                    ],
                ],
            ], $context);
        }
    }

    public function deleteCustomFieldSet(Context $context): void
    {
        $customFieldSetIds = $this->getCustomFieldSetIds(self::AGE_VERIFICATION_CUSTOM_FIELD_SET, $context);

        if ($customFieldSetIds) {
            $idsToDelete = array_map(static fn ($id) => ['id' => $id], $customFieldSetIds->getIds());
            $this->customFieldSetRepository->delete($idsToDelete, $context);
        }
    }

    private function getCustomFieldSetIds(string $customFieldSetName, Context $context): ?IdSearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $customFieldSetName));

        $customFieldSetIds = $this->customFieldSetRepository->searchIds($criteria, $context);

        return $customFieldSetIds->getTotal() > 0 ? $customFieldSetIds : null;
    }
}