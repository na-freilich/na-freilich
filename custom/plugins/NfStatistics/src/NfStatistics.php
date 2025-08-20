<?php declare(strict_types=1);

namespace Nf\Statistics;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class NfStatistics extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $criteria = (new Criteria())->
        addAssociation('customFields')->
        addFilter(new EqualsFilter('name', 'nf_order'))->
        addFilter(new EqualsFilter('customFields.name', 'nf_order_product_detail_page_layout'));
        $existCustomField = $customFieldSetRepository->search($criteria, $installContext->getContext());
        if ($existCustomField->getTotal() == 0)
        {
            $customFieldSetRepository->upsert([
                [
                    'name' => 'nf_order',
                    'config' => [
                        'label' => [
                            'de-DE' => 'NF Bestellung',
                            'en-GB' => 'NF Order'
                        ]
                    ],
                    'relations' => [[
                        'entityName' => 'order'
                    ]],
                    'customFields' => [
                        [
                            'name' => 'nf_order_product_detail_page_layout',
                            'type' => CustomFieldTypes::SELECT,
                            'config' => [
                                'label' => [
                                    'de-DE' => 'Layout der Produktdetailseite',
                                    'en-GB' => 'Product detail page layout'
                                ],
                                'entity' => 'cms_page',
                                'componentName' => 'sw-entity-single-select',
                                'customFieldType' => CustomFieldTypes::ENTITY,
                                'customFieldPosition' => 10
                            ]
                        ]
                    ]
                ]
            ], $installContext->getContext());
        }
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if (!$uninstallContext->keepUserData()) {
            /** @var Connection $connection */
            $connection = $this->container->get(Connection::class);
            $connection->executeStatement('DROP TABLE IF EXISTS `nf_stat_product_views`;');
            $connection->executeStatement('DROP TABLE IF EXISTS `nf_stat_product_layout_views`;');

            $customFieldSetRepository = $this->container->get('custom_field_set.repository');
            $criteria = (new Criteria())->
            addFilter(new EqualsFilter('name', 'nf_order'));
            $result = $customFieldSetRepository->searchIds($criteria, $uninstallContext->getContext());
            if ($result->getTotal()) {
                $customFieldSetRepository->delete([['id' => $result->firstId()]], $uninstallContext->getContext());
            }
        }

    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);

        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $criteria = (new Criteria())->
        addAssociation('customFields')->
        addFilter(new EqualsFilter('name', 'nf_order'))->
        addFilter(new EqualsFilter('customFields.name', 'nf_order_product_detail_page_layout'));
        $existCustomField = $customFieldSetRepository->search($criteria, $updateContext->getContext());
        if ($existCustomField->getTotal() == 0)
        {
            $customFieldSetRepository->upsert([
                [
                    'name' => 'nf_order',
                    'config' => [
                        'label' => [
                            'de-DE' => 'NF Bestellung',
                            'en-GB' => 'NF Order'
                        ]
                    ],
                    'relations' => [[
                        'entityName' => 'order'
                    ]],
                    'customFields' => [
                        [
                            'name' => 'nf_order_product_detail_page_layout',
                            'type' => CustomFieldTypes::SELECT,
                            'config' => [
                                'label' => [
                                    'de-DE' => 'Layout der Produktdetailseite',
                                    'en-GB' => 'Product detail page layout'
                                ],
                                'entity' => 'cms_page',
                                'componentName' => 'sw-entity-single-select',
                                'customFieldType' => CustomFieldTypes::ENTITY,
                                'customFieldPosition' => 10
                            ]
                        ]
                    ]
                ]
            ], $updateContext->getContext());
        }
    }
}