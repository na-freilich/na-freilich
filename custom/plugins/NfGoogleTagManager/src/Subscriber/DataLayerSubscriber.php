<?php declare(strict_types=1);

namespace NfGoogleTagManager\Subscriber;

use NfGoogleTagManager\Components\BeginCheckoutTracker;
use NfGoogleTagManager\Components\DataLayerMapper;
use NfGoogleTagManager\Components\ItemPropertyMap;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ProductListingStruct;
use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductSearchCriteriaEvent;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPage;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class DataLayerSubscriber implements EventSubscriberInterface
{
    /**
     * @param SalesChannelRepository<SalesChannelProductEntity> $productRepository
     */
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly DataLayerMapper $mapper,
        private readonly SalesChannelRepository $productRepository,
        private readonly ItemPropertyMap $itemPropertyMap,
        private readonly BeginCheckoutTracker $beginCheckoutTracker
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
            NavigationPageLoadedEvent::class => 'onNavigationPageLoaded',
            CheckoutFinishPageLoadedEvent::class => 'onCheckoutFinishLoaded',
            SearchPageLoadedEvent::class => 'onSearchPageLoaded',
            CheckoutCartPageLoadedEvent::class => 'onCartPageLoaded',
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmLoaded',
            CheckoutRegisterPageLoadedEvent::class => 'onCheckoutRegisterLoaded',
            OffcanvasCartPageLoadedEvent::class => 'onOffcanvasCartLoaded',
            ProductListingCriteriaEvent::class => 'onListingCriteria',
            ProductSearchCriteriaEvent::class => 'onListingCriteria',
        ];
    }

    public function onListingCriteria(ProductListingCriteriaEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        if (!$this->isActive($salesChannelId)) {
            return;
        }

        if (!$this->isEnabled('eventViewItemList', $salesChannelId)
            && !$this->isEnabled('eventAddToCart', $salesChannelId)
            && !$this->isEnabled('eventSelectItem', $salesChannelId)
        ) {
            return;
        }

        $criteria = $event->getCriteria();
        $criteria->addAssociation('manufacturer');
        $criteria->addAssociation('seoCategory');
        $criteria->addAssociation('categories');
        $criteria->addAssociation('properties.group');
        $criteria->addAssociation('options.group');
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        if (!$this->isActive($salesChannelId) || !$this->isEnabled('eventViewItem', $salesChannelId)) {
            return;
        }

        $context = $event->getSalesChannelContext();
        $event->getPage()->addExtension('nfGtmDataLayer', new ArrayEntity(
            $this->mapper->mapViewItem($event->getPage()->getProduct(), $context, $this->propertyMap($context))
        ));
    }

    public function onNavigationPageLoaded(NavigationPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        if (!$this->isActive($salesChannelId) || !$this->isEnabled('eventViewItemList', $salesChannelId)) {
            return;
        }

        $page = $event->getPage();
        if (!$page instanceof NavigationPage) {
            return;
        }

        $products = $this->productsFromCmsListing($page);
        if ($products === []) {
            return;
        }

        $category = $page->getCategory();
        $listName = $category ? (string) ($category->getTranslation('name') ?? '') : '';
        $listId = $category ? (string) $category->getId() : '';
        $context = $event->getSalesChannelContext();
        $mapped = $this->mapper->mapItemList($products, $context, $listId, $listName, $listName, $this->propertyMap($context));
        if ($mapped === null) {
            return;
        }

        $page->addExtension('nfGtmDataLayer', new ArrayEntity($mapped));
    }

    public function onSearchPageLoaded(SearchPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        if (!$this->isActive($salesChannelId)) {
            return;
        }

        $page = $event->getPage();
        $searchTerm = $page->getSearchTerm();

        if ($this->isEnabled('eventSearch', $salesChannelId)) {
            $page->addExtension('nfGtmSearchDataLayer', new ArrayEntity([
                'event' => 'search',
                'search_term' => $searchTerm,
            ]));
        }

        if (!$this->isEnabled('eventViewItemList', $salesChannelId)) {
            return;
        }

        $mapped = $this->mapper->mapItemList(
            $page->getListing()->getEntities(),
            $event->getSalesChannelContext(),
            'search_results',
            $searchTerm,
            '',
            $this->propertyMap($event->getSalesChannelContext())
        );
        if ($mapped === null) {
            return;
        }

        $page->addExtension('nfGtmDataLayer', new ArrayEntity($mapped));
    }

    public function onCheckoutFinishLoaded(CheckoutFinishPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        if (!$this->isActive($salesChannelId) || !$this->isEnabled('eventPurchase', $salesChannelId)) {
            return;
        }

        $order = $event->getPage()->getOrder();
        $context = $event->getSalesChannelContext();
        $event->getPage()->addExtension('nfGtmDataLayer', new ArrayEntity(
            $this->mapper->mapPurchase($order, $context, $this->productsForOrder($order, $context), $this->propertyMap($context))
        ));
    }

    public function onCartPageLoaded(CheckoutCartPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        if (!$this->isActive($salesChannelId)) {
            return;
        }

        $mapped = $this->mapCart($event->getPage()->getCart(), $event->getSalesChannelContext());
        if ($mapped === null) {
            return;
        }

        $this->attachCartItems($event->getPage(), $mapped);
        if ($this->isEnabled('eventViewCart', $salesChannelId)) {
            $event->getPage()->addExtension('nfGtmDataLayer', new ArrayEntity($mapped));
        }
    }

    public function onOffcanvasCartLoaded(OffcanvasCartPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        if (!$this->isActive($salesChannelId)) {
            return;
        }

        $mapped = $this->mapCart($event->getPage()->getCart(), $event->getSalesChannelContext());
        if ($mapped === null) {
            return;
        }

        $this->attachCartItems($event->getPage(), $mapped);
    }

    public function onCheckoutRegisterLoaded(CheckoutRegisterPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        if (!$this->isActive($salesChannelId)) {
            return;
        }

        $mapped = $this->mapCart($event->getPage()->getCart(), $event->getSalesChannelContext());
        if ($mapped === null) {
            return;
        }

        $mapped['event'] = 'begin_checkout';
        if ($this->shouldSendBeginCheckout($event->getRequest(), true)) {
            $event->getPage()->addExtension('nfGtmDataLayer', new ArrayEntity($mapped));
        }
    }

    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        if (!$this->isActive($salesChannelId)) {
            return;
        }

        $page = $event->getPage();
        $cart = $page->getCart();
        $context = $event->getSalesChannelContext();
        $mapped = $this->mapCart($cart, $context);
        if ($mapped === null) {
            return;
        }

        $this->attachCartItems($page, $mapped);

        if ($this->shouldSendBeginCheckout($event->getRequest(), false)) {
            $page->addExtension('nfGtmDataLayer', new ArrayEntity([
                'event' => 'begin_checkout',
                'ecommerce' => $mapped['ecommerce'],
            ]));
        }

        if (!$this->isEnabled('eventCheckoutSteps', $salesChannelId)) {
            return;
        }

        $shippingEcommerce = $mapped['ecommerce'];
        $shippingEcommerce['shipping_tier'] = $this->shippingMethodName($cart);
        $page->addExtension('nfGtmShippingDataLayer', new ArrayEntity([
            'event' => 'add_shipping_info',
            'ecommerce' => $shippingEcommerce,
        ]));

        $paymentEcommerce = $mapped['ecommerce'];
        $paymentEcommerce['payment_type'] = $this->paymentMethodName($context);
        $page->addExtension('nfGtmPaymentDataLayer', new ArrayEntity([
            'event' => 'add_payment_info',
            'ecommerce' => $paymentEcommerce,
        ]));
    }

    /**
     * @return array<string, string>
     */
    private function propertyMap(SalesChannelContext $context): array
    {
        return $this->itemPropertyMap->forSalesChannel($context->getSalesChannelId());
    }

    private function shouldSendBeginCheckout(Request $request, bool $register): bool
    {
        if (!$request->hasSession()) {
            return true;
        }

        $session = $request->getSession();

        return $register
            ? $this->beginCheckoutTracker->claimOnRegister($session)
            : $this->beginCheckoutTracker->claimOnConfirm($session);
    }

    /**
     * @param array<string, mixed> $mapped
     */
    private function attachCartItems(Page $page, array $mapped): void
    {
        $page->addExtension('nfGtmCartItems', new ArrayEntity($mapped));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapCart(Cart $cart, SalesChannelContext $context): ?array
    {
        return $this->mapper->mapCart(
            $cart,
            $context,
            $this->productsForCart($cart, $context),
            $this->propertyMap($context)
        );
    }

    /**
     * @return array<string, SalesChannelProductEntity>
     */
    private function productsForCart(Cart $cart, SalesChannelContext $context): array
    {
        $ids = [];
        foreach ($cart->getLineItems() as $lineItem) {
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }
            $id = $lineItem->getReferencedId();
            if (is_string($id) && $id !== '') {
                $ids[] = $id;
            }
        }

        return $this->loadProducts($ids, $context);
    }

    /**
     * @return array<string, SalesChannelProductEntity>
     */
    private function productsForOrder(OrderEntity $order, SalesChannelContext $context): array
    {
        $ids = [];
        $lineItems = $order->getLineItems();
        if ($lineItems === null) {
            return [];
        }

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }
            $id = $lineItem->getReferencedId();
            if (is_string($id) && $id !== '') {
                $ids[] = $id;
            }
        }

        return $this->loadProducts($ids, $context);
    }

    /**
     * @param list<string> $ids
     *
     * @return array<string, SalesChannelProductEntity>
     */
    private function loadProducts(array $ids, SalesChannelContext $context): array
    {
        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return [];
        }

        $criteria = new Criteria($ids);
        $criteria->addAssociation('manufacturer');
        $criteria->addAssociation('seoCategory');
        $criteria->addAssociation('categories');
        $criteria->addAssociation('properties.group');
        $criteria->addAssociation('options.group');

        $products = [];
        foreach ($this->productRepository->search($criteria, $context)->getEntities() as $product) {
            if ($product instanceof SalesChannelProductEntity) {
                $products[$product->getId()] = $product;
            }
        }

        return $products;
    }

    /**
     * @return list<SalesChannelProductEntity>
     */
    private function productsFromCmsListing(NavigationPage $page): array
    {
        $cmsPage = $page->getCmsPage();
        if ($cmsPage === null || $cmsPage->getSections() === null) {
            return [];
        }

        foreach ($cmsPage->getSections() as $section) {
            if ($section->getBlocks() === null) {
                continue;
            }

            foreach ($section->getBlocks() as $block) {
                if ($block->getType() !== 'product-listing' || $block->getSlots() === null) {
                    continue;
                }

                foreach ($block->getSlots() as $slot) {
                    $data = $slot->getData();
                    if (!$data instanceof ProductListingStruct || $data->getListing() === null) {
                        continue;
                    }

                    $products = [];
                    foreach ($data->getListing()->getElements() as $product) {
                        if ($product instanceof SalesChannelProductEntity) {
                            $products[] = $product;
                        }
                    }

                    return $products;
                }
            }
        }

        return [];
    }

    private function shippingMethodName(Cart $cart): string
    {
        $delivery = $cart->getDeliveries()->first();
        $method = $delivery ? $delivery->getShippingMethod() : null;
        if ($method === null) {
            return 'Standard';
        }

        return (string) ($method->getTranslation('name') ?? $method->getName() ?? 'Standard');
    }

    private function paymentMethodName(SalesChannelContext $context): string
    {
        $method = $context->getPaymentMethod();
        if ($method === null) {
            return 'Standard';
        }

        return (string) ($method->getTranslation('name') ?? $method->getName() ?? 'Standard');
    }

    private function isActive(string $salesChannelId): bool
    {
        return $this->isEnabled('active', $salesChannelId);
    }

    private function isEnabled(string $key, string $salesChannelId): bool
    {
        return (bool) $this->systemConfigService->get('NfGoogleTagManager.config.' . $key, $salesChannelId);
    }
}
