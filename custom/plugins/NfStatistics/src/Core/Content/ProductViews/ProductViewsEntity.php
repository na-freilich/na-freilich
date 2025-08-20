<?php declare(strict_types=1);

namespace Nf\Statistics\Core\Content\ProductViews;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ProductViewsEntity extends Entity
{
    use EntityIdTrait;

    protected ?\DateTimeInterface $viewDate;

    protected int $views;

    protected string $productId;

    protected ProductEntity $product;

    public function getViewDate(): ?\DateTimeInterface
    {
        return $this->viewDate;
    }

    public function setViewDate(?\DateTimeInterface $viewDate): void
    {
        $this->viewDate = $viewDate;
    }

    public function getViews(): int
    {
        return $this->views;
    }

    public function setViews(int $views): void
    {
        $this->views = $views;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProduct(): ProductEntity
    {
        return $this->product;
    }

    public function setProduct(ProductEntity $product): void
    {
        $this->product = $product;
    }
}