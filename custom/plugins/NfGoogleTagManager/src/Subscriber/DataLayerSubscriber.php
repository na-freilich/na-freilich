<?php declare(strict_types=1);

namespace NfGoogleTagManager\Subscriber;

use NfGoogleTagManager\Components\DataLayerMapper;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Storefront\Page\Navigation\NavigationPage;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;

class DataLayerSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;
    private DataLayerMapper $mapper;

    public function __construct(
        SystemConfigService $systemConfigService,
        DataLayerMapper $mapper
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->mapper = $mapper;
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
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();

        if (!$this->systemConfigService->get('NfGoogleTagManager.config.active', $salesChannelId)) {
            return;
        }

        if (!$this->systemConfigService->get('NfGoogleTagManager.config.eventViewItem', $salesChannelId)) {
            return;
        }

        $product = $event->getPage()->getProduct();
        $context = $event->getSalesChannelContext();

        $dataLayerData = $this->mapper->mapViewItem($product, $context);

        $event->getPage()->addExtension('nfGtmDataLayer', new ArrayEntity($dataLayerData));
    }

    public function onNavigationPageLoaded(NavigationPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();

        if (!$this->systemConfigService->get('NfGoogleTagManager.config.active', $salesChannelId)) {
            return;
        }

        if (!$this->systemConfigService->get('NfGoogleTagManager.config.eventViewItemList', $salesChannelId)) {
            return;
        }

        $page = $event->getPage();
        $context = $event->getSalesChannelContext();

        if (!$page instanceof NavigationPage || !$page->getCmsPage()) {
            return;
        }

        $cmsPage = $page->getCmsPage();
        $products = [];

        if ($cmsPage->getSections()) {
            foreach ($cmsPage->getSections() as $section) {
                if (!$section->getBlocks()) {
                    continue;
                }

                foreach ($section->getBlocks() as $block) {
                    if ($block->getType() !== 'product-listing') {
                        continue;
                    }

                    $slots = $block->getSlots();
                    if (!$slots) {
                        continue;
                    }

                    foreach ($slots as $slot) {
                        $structData = $slot->getData();
                        if ($structData instanceof \Shopware\Core\Content\Cms\SalesChannel\Struct\ProductListingStruct) {
                            $listingResult = $structData->getListing();

                            if ($listingResult) {

                                $products = $listingResult->getElements();
                                break 3;
                            }
                        }
                    }
                }
            }
        }

        if (empty($products)) {
            return;
        }

        $items = [];
        foreach ($products as $product) {
            $items[] = $this->mapper->mapProductItem($product, $context);
        }

        $dataLayerData = [
            'event' => 'view_item_list',
            'ecommerce' => [
                'currency' => $context->getCurrency()->getIsoCode(),
                'items' => $items
            ]
        ];

        $event->getPage()->addExtension('nfGtmDataLayer', new ArrayEntity($dataLayerData));
    }

    public function onCheckoutFinishLoaded(CheckoutFinishPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();

        if (!$this->systemConfigService->get('NfGoogleTagManager.config.active', $salesChannelId)) {
            return;
        }

        if (!$this->systemConfigService->get('NfGoogleTagManager.config.eventPurchase', $salesChannelId)) {
            return;
        }

        $page = $event->getPage();
        $order = $page->getOrder();
        $context = $event->getSalesChannelContext();

        $dataLayerData = $this->mapper->mapPurchase($order, $context);

        $event->getPage()->addExtension('nfGtmDataLayer', new ArrayEntity($dataLayerData));
    }

    public function onSearchPageLoaded(SearchPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();

        if (!$this->systemConfigService->get('NfGoogleTagManager.config.active', $salesChannelId)) {
            return;
        }

        if (!$this->systemConfigService->get('NfGoogleTagManager.config.eventSearch', $salesChannelId)) {
            return;
        }

        $page = $event->getPage();
        $searchTerm = $page->getSearchTerm();
        $dataLayerData = [
            'event' => 'search',
            'search_term' => $searchTerm
        ];

        $event->getPage()->addExtension('nfGtmDataLayer', new ArrayEntity($dataLayerData));
    }

    public function onCartPageLoaded(CheckoutCartPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();

        if (!$this->systemConfigService->get('NfGoogleTagManager.config.active', $salesChannelId)) {
            return;
        }

        if (!$this->systemConfigService->get('NfGoogleTagManager.config.eventViewCart', $salesChannelId)) {
            return;
        }

        $page = $event->getPage();
        $cart = $page->getCart();
        $context = $event->getSalesChannelContext();

        $dataLayerData = $this->mapper->mapCart($cart, $context);

        $event->getPage()->addExtension('nfGtmDataLayer', new ArrayEntity($dataLayerData));
    }

    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();

        if (!$this->systemConfigService->get('NfGoogleTagManager.config.active', $salesChannelId)) {
            return;
        }

        $page = $event->getPage();
        $cart = $page->getCart();
        $context = $event->getSalesChannelContext();

        $beginCheckoutData = $this->mapper->mapCart($cart, $context);
        $beginCheckoutData['event'] = 'begin_checkout';
        $page->addExtension('nfGtmDataLayer', new ArrayEntity($beginCheckoutData));

        if ($this->systemConfigService->get('NfGoogleTagManager.config.eventCheckoutSteps', $salesChannelId)) {
            $shippingData = $this->mapper->mapCart($cart, $context);
            $shippingData['event'] = 'add_shipping_info';

            $shippingMethodName = 'Standard';
            if ($cart->getDeliveries()->first() && $cart->getDeliveries()->first()->getShippingMethod()) {
                $shippingMethodName = $cart->getDeliveries()->first()->getShippingMethod()->getTranslation('name')
                    ?? $cart->getDeliveries()->first()->getShippingMethod()->getName();
            }
            $shippingData['ecommerce']['shipping_tier'] = $shippingMethodName;
            $page->addExtension('nfGtmShippingDataLayer', new ArrayEntity($shippingData));
//dd($shippingData);
            $paymentData = $this->mapper->mapCart($cart, $context);
            $paymentData['event'] = 'add_payment_info';

            $paymentMethodName = 'Standard';
            if ($context->getPaymentMethod()) {
                $paymentMethodName = $context->getPaymentMethod()->getTranslation('name')
                    ?? $context->getPaymentMethod()->getName();
            }
            $paymentData['ecommerce']['payment_type'] = $paymentMethodName;
            $page->addExtension('nfGtmPaymentDataLayer', new ArrayEntity($paymentData));
        }
    }
}