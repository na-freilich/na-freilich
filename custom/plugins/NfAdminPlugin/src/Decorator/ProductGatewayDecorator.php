<?php

namespace Nf\AdminPlugin\Decorator;

use Shopware\Core\Content\Product\Cart\ProductGateway;
use Nf\AdminPlugin\Service\ProductService;
use Shopware\Core\Content\Product\Cart\ProductGatewayInterface;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProductGatewayDecorator extends ProductGateway
{
    public function __construct(
        private readonly ProductGatewayInterface $decorated,
        private readonly ProductService $productService
    )
    {
    }

    public function get(array $ids, SalesChannelContext $context): ProductCollection
    {
        $result = $this->decorated->get($ids, $context);

        foreach ($result->getElements() as $product) {
            $this->productService->updatePrice($product, $context);
        }

        return $result;
    }
}