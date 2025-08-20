<?php declare(strict_types=1);

namespace Nf\Statistics\Controller;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class DevController extends StorefrontController
{
    private Connection $connection;

    private EntityRepository $categoryRepository;
    private EntityRepository $productRepository;

    public function __construct(Connection $connection, EntityRepository $categoryRepository, EntityRepository $productRepository)
    {
        $this->connection = $connection;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
    }

    #[Route(path: 'create-custom-fields', name: 'nf.create.custom.fields', options: ['seo' => false], methods: ['GET'])]
    public function createCustomFields(Request $request, SalesChannelContext $salesChannelContext, Context $context)
    {
/*        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $criteria = (new Criteria())->
        addAssociation('customFields')->
        addFilter(new EqualsFilter('name', 'nf_order'))->
        addFilter(new EqualsFilter('customFields.name', 'nf_order_product_detail_page_layout'));
        $existCustomField = $customFieldSetRepository->search($criteria, $context);
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
            ], $context);
        }*/

/*        $criteria = (new Criteria())->
        addFilter(new EqualsFilter('name', 'nf_order'));
        $result = $customFieldSetRepository->searchIds($criteria, $context);
        if ($result->getTotal()) {
            $customFieldSetRepository->delete([['id' => $result->firstId()]], $context);
        }*/

        dd('ok');
    }
}