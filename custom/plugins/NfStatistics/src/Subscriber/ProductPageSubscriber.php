<?php

namespace Nf\Statistics\Subscriber;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedCriteriaEvent;
use Shopware\Core\Content\Category\Service\CategoryBreadcrumbBuilder;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoaderInterface;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\Exception\ProductNotFoundException;
use Shopware\Core\Content\Product\SalesChannel\AbstractProductCloseoutFilterFactory;
use Shopware\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductDefinition;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Adapter\Cache\CacheClearer;
use Shopware\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Plugin\Event\PluginPostActivateEvent;
use Shopware\Core\Framework\Plugin\Event\PluginPostUpdateEvent;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ProductPageSubscriber implements EventSubscriberInterface
{
    private Connection $connection;

    private ParameterBagInterface $params;

    private SalesChannelCmsPageLoaderInterface $cmsPageLoader;

    private SalesChannelProductDefinition $productDefinition;

    private SalesChannelRepository $salesChannelProductRepository;

    private SystemConfigService $config;

    private AbstractProductCloseoutFilterFactory $productCloseoutFilterFactory;

    private CategoryBreadcrumbBuilder $breadcrumbBuilder;

    private RequestStack $requestStack;

    private $currentDate;

    private $productPageLoaded;

    private $cacheClearer;

    public function __construct(
        Connection $connection,
        ParameterBagInterface $params,
        SalesChannelCmsPageLoaderInterface $cmsPageLoader,
        SalesChannelProductDefinition $productDefinition,
        SalesChannelRepository $salesChannelProductRepository,
        SystemConfigService $config,
        AbstractProductCloseoutFilterFactory $productCloseoutFilterFactory,
        CategoryBreadcrumbBuilder $breadcrumbBuilder,
        RequestStack $requestStack,
        CacheClearer $cacheClearer
    )
    {
        $this->connection = $connection;
        $this->params = $params;
        $this->cmsPageLoader = $cmsPageLoader;
        $this->productDefinition = $productDefinition;
        $this->salesChannelProductRepository = $salesChannelProductRepository;
        $this->config = $config;
        $this->productCloseoutFilterFactory = $productCloseoutFilterFactory;
        $this->breadcrumbBuilder = $breadcrumbBuilder;
        $this->requestStack = $requestStack;
        $this->cacheClearer = $cacheClearer;

        $this->currentDate = date('Y-m-d');
        $this->productPageLoaded = false;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
            CheckoutOrderPlacedCriteriaEvent::class => 'onCheckoutOrderPlacedCriteriaEvent',
            BeforeLineItemAddedEvent::class => 'onBeforeLineItemAddedEvent',
            ResponseEvent::class => ['onResponseEvent', -2000],
            PluginPostActivateEvent::class => 'onPluginPostActivate',
            PluginPostUpdateEvent::class => 'onPluginPostUpdate'
        ];
    }

    public function onPluginPostUpdate(PluginPostUpdateEvent $event)
    {
        if ($event->getPlugin()->getName() == 'NfStatistics')
        {
            $this->cacheClearer->clear();
        }
    }

    public function onPluginPostActivate(PluginPostActivateEvent $event)
    {
        if ($event->getPlugin()->getName() == 'NfStatistics')
        {
            $this->cacheClearer->clear();
        }
    }

    public function onResponseEvent(ResponseEvent $event)
    {
        if ($this->productPageLoaded)
        {
            $response = $event->getResponse();
            $response->headers->addCacheControlDirective('no-store');
            $event->setResponse($response);
        }
    }

    public function onBeforeLineItemAddedEvent(BeforeLineItemAddedEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        $userSession = $request->getSession()->getId();
        $sql = '
            select 
                id
            from nf_stat_product_layout_views
            where
                user_session = "'.$userSession.'"
            order by view_date desc
            limit 1
        ';
        $cart = $event->getCart();
        $item = $event->getLineItem();
        $lineItems = $cart->getLineItems();

        $productLayoutViewsId = $this->connection->fetchOne($sql);
        if (!$productLayoutViewsId)
        {
            $sql = '
                select
                    cms_page_id as cmsPageId
                from product
                where
                    id = unhex("'.$item->getId().'")
            ';
            $productCmsPageId = $this->connection->fetchOne($sql)?? $this->getDefaultProductCmsPageId();
            $productLayoutViewsId = Uuid::randomBytes();

            $data = [
                'id' => $productLayoutViewsId,
                'user_session' => $userSession,
                'views' => 1,
                'view_date' => $this->currentDate,
                'created_at' => date('Y-m-d H:i:s')
            ];

            if ($productCmsPageId)
                $data['product_layout_id'] = hex2bin($productCmsPageId);

            $this->connection->insert(
                'nf_stat_product_layout_views',
                $data,
                [
                    'id' => ParameterType::BINARY,
                    'product_layout_id' => ParameterType::BINARY
                ]

            );

        }

        foreach ($lineItems as $lineItem)
        {
            if ($lineItem->getId() == $item->getId() && !$lineItem->hasExtension('nfStatProductLayoutViews'))
            {
		$lineItem->addExtension('nfStatProductLayoutViews', new ArrayStruct(['id' => bin2hex($productLayoutViewsId)]));
            }
        }
        $cart->setLineItems($lineItems);
    }

    public function onCheckoutOrderPlacedCriteriaEvent(CheckoutOrderPlacedCriteriaEvent $event)
    {
        $orderId = $event->getCriteria()->getIds()[0];
        $token = $event->getSalesChannelContext()->getToken();

        //////////////////////////////////////////////////////////////////////////////
        // Code from vendor/shopware/core/Checkout/Cart/CartPersister.php
        // function load()
        if ($this->columnExists($this->connection, 'cart', 'payload'))
        {
            $cartRequestResult = $this->connection->fetchAssociative(
                'SELECT `cart`.`payload`, `cart`.`compressed` FROM cart WHERE `token` = :token',
                ['token' => $token]
            );
        }
        else
        {
            $cartRequestResult = $this->connection->fetchAssociative(
                'SELECT `cart`.`cart` as payload, 0 as `compressed` FROM cart WHERE `token` = :token',
                ['token' => $token]
            );
        }

        if (!\is_array($cartRequestResult)) {
            throw CartException::tokenNotFound($token);
        }

        $cart = $cartRequestResult['compressed'] ? CacheValueCompressor::uncompress($cartRequestResult['payload']) : unserialize((string) $cartRequestResult['payload']);

        if (!$cart instanceof Cart) {
            throw CartException::deserializeFailed();
        }
        //////////////////////////////////////////////////////////////////////////////

        foreach ($cart->getLineItems() as $lineItem)
        {
            if (!$lineItem->getExtension('nfStatProductLayoutViews'))
                continue;

            $nfStatProductLayoutViewsId = $lineItem->getExtension('nfStatProductLayoutViews')->getVars()['id'];
            $sql = '
                select
                    lower(hex(product_layout_id)) as productLayoutId
                from nf_stat_product_layout_views
                where
                    id = unhex("'.$nfStatProductLayoutViewsId.'")
            ';
            $productLayoutId = $this->connection->fetchOne($sql);
            $sql = '
                update nf_stat_product_layout_views
                set
                    order_quantity = order_quantity + 1
                where
                    id = unhex("'.$nfStatProductLayoutViewsId.'")
            ';

            $this->connection->executeStatement($sql);
        }

        $sql = '
            select
                custom_fields as customFields
            from `order`
            where
                id = unhex(:id)
        ';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('id', $orderId);
        $orderCustomFields = json_decode($stmt->executeQuery()->fetchOne())?? new \stdClass();

        $orderCustomFields->nf_order_product_detail_page_layout = $productLayoutId;
        $sql = '
            update `order`
            set 
                custom_fields = :customFields
            where
                id = unhex(:id)
        ';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('customFields', json_encode($orderCustomFields));
        $stmt->bindValue('id', $orderId);
        $stmt->executeStatement();
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $pageLoadedEvent): void
    {
        $userSession = $pageLoadedEvent->getRequest()->getSession()->getId();
        $product = $pageLoadedEvent->getPage()->getProduct();
        $pageProductCmsPageId = $product->getCmsPageId() ?? $this->getDefaultProductCmsPageId();

        $this->updateViewsNumber(
            $product->getId(),
            $userSession,
            $pageProductCmsPageId);
    }

    private function updateViewsNumber($productId, $userSession, $pageProductCmsPageId)
    {
        $sql = '
            select 
                id
            from nf_stat_product_views
            where
                user_session = "'.$userSession.'" and
                product_id = unhex("'.$productId.'") and
                view_date = "'.$this->currentDate.'" 
        ';
        $stmt = $this->connection->prepare($sql);
        $searchResult = $stmt->executeQuery();

        if ($searchResult->rowCount() == 0)
        {
            $this->connection->insert(
                'nf_stat_product_views',
                [
                    'id' => Uuid::randomBytes(),
                    'user_session' => $userSession,
                    'product_id' => hex2bin($productId),
                    'views' => 1,
                    'view_date' => $this->currentDate,
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => ParameterType::BINARY,
                    'product_id' => ParameterType::BINARY,
                ]
            );
        }

        $sql = '
            select 
                id
            from nf_stat_product_layout_views
            where
                user_session = "'.$userSession.'" and
                product_layout_id = unhex("'.$pageProductCmsPageId.'") and
                view_date = "'.$this->currentDate.'" 
        ';
        $stmt = $this->connection->prepare($sql);
        $searchResult = $stmt->executeQuery();

        if ($searchResult->rowCount() == 0)
        {
            $this->connection->insert(
                'nf_stat_product_layout_views',
                [
                    'id' => Uuid::randomBytes(),
                    'user_session' => $userSession,
                    'product_layout_id' => hex2bin($pageProductCmsPageId),
                    'views' => 1,
                    'view_date' => $this->currentDate,
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => ParameterType::BINARY,
                    'product_layout_id' => ParameterType::BINARY
                ]

            );
        }
    }

    private function getDefaultProductCmsPageId()
    {
        $sql = '
            select
                lower(hex(id))
            from cms_page
            where
                type = "product_detail" and
                locked = 1
            limit 1
        ';

        return $this->connection->fetchOne($sql);
    }

    //////////////////////////////////////////////////////////////////////////////
    // Code from vendor/shopware/core/Framework/DataAbstractionLayer/Dbal/EntityDefinitionQueryHelper.php
    private function columnExists(Connection $connection, string $table, string $column): bool
    {
        $exists = $connection->fetchOne(
            'SHOW COLUMNS FROM ' . self::escape($table) . ' WHERE `Field` LIKE :column',
            ['column' => $column]
        );

        return !empty($exists);
    }

    private function escape(string $string): string
    {
        if (mb_strpos($string, '`') !== false) {
            throw new \InvalidArgumentException('Backtick not allowed in identifier');
        }

        return '`' . $string . '`';
    }
    //////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////////////////////////////////////////
    // Code from vendor/shopware/core/Content/Product/SalesChannel/Detail/ProductDetailRoute.php
    private function checkVariantListingConfig(string $productId, SalesChannelContext $context): ?string
    {
        /** @var SalesChannelProductEntity|null $product */
        $product = $this->salesChannelProductRepository->search(new Criteria([$productId]), $context)->first();

        if ($product === null || $product->getParentId() !== null) {
            return null;
        }

        if (($listingConfig = $product->getVariantListingConfig()) === null || $listingConfig->getDisplayParent() !== true) {
            return null;
        }

        return $listingConfig->getMainVariantId();
    }

    private function findBestVariant(string $productId, SalesChannelContext $context): string
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('product.parentId', $productId))
            ->addSorting(new FieldSorting('product.price'))
            ->addSorting(new FieldSorting('product.available'))
            ->setLimit(1);

        $criteria->setTitle('product-detail-route::find-best-variant');
        $variantId = $this->salesChannelProductRepository->searchIds($criteria, $context);

        return $variantId->firstId() ?? $productId;
    }

    private function addFilters(SalesChannelContext $context, Criteria $criteria): void
    {
        $criteria->addFilter(
            new ProductAvailableFilter($context->getSalesChannel()->getId(), ProductVisibilityDefinition::VISIBILITY_LINK)
        );

        $salesChannelId = $context->getSalesChannel()->getId();

        $hideCloseoutProductsWhenOutOfStock = $this->config->get('core.listing.hideCloseoutProductsWhenOutOfStock', $salesChannelId);

        if ($hideCloseoutProductsWhenOutOfStock) {
            $filter = $this->productCloseoutFilterFactory->create($context);
            $filter->addQuery(new EqualsFilter('product.parentId', null));
            $criteria->addFilter($filter);
        }
    }

    private function createCriteria(string $pageId, Request $request): Criteria
    {
        $criteria = new Criteria([$pageId]);
        $criteria->setTitle('product::cms-page');

        $slots = $request->get('slots');

        if (\is_string($slots)) {
            $slots = explode('|', $slots);
        }

        if (!empty($slots) && \is_array($slots)) {
            $criteria
                ->getAssociation('sections.blocks')
                ->addFilter(new EqualsAnyFilter('slots.id', $slots));
        }

        return $criteria;
    }
    //////////////////////////////////////////////////////////////////////////////
}
