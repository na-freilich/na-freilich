<?php

namespace Nf\AdminPlugin\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ProductService
{

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SystemConfigService $configService
    )
    {}

    public function updatePrice($product, SalesChannelContext $context=null): void
    {
        $recalculateGrossPrices = $this->configService->getString(
            'AdminPlugin.config.recalculateGrossPrices'
        );

        if(!$recalculateGrossPrices)
            return;

        $productTaxId = $product->getTaxId();
        if (!$context)
        {
            $request = $this->requestStack->getCurrentRequest();
            if (!$request) {
                return;
            }

            $context = $request->attributes->get('sw-sales-channel-context');
            if (!$context) {
                return;
            }
        }

        $customer = $context->getCustomer();
        if (!$customer) {
            return;
        }

        $taxRules = $context->getTaxRules();

        $taxRate = $taxRules->get($productTaxId)->getRules()->first()->getTaxRate();
        if(!$taxRate)
            return;

        if ($taxRate == $product->getTax()->getTaxRate())
            return;


        $prices = $product->getPrice();

        if (!$prices)
            return;

        foreach ($prices as $price) {
            $netPrice = $price->getNet();
            $grossPrice = round($netPrice * ( 1+ $taxRate/100),2);
            $price->setGross($grossPrice);
        }

        $prices = $product->getPrices();

        if (!$prices)
            return;

        foreach ($prices as $priceEl) {
            $price = $priceEl->getPrice()->first();
            $netPrice = $price->getNet();
            $grossPrice = round($netPrice * ( 1+ $taxRate/100),2);
            $price->setGross($grossPrice);
        }
    }

}