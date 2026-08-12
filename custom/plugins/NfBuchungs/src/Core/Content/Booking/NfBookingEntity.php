<?php declare(strict_types=1);

namespace Nf\Booking\Core\Content\Booking;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Nf\Booking\Core\Content\Booking\BookingItem\NfBookingItemCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Nf\Booking\Core\Content\BookingSeriesDiscount\NfBookingSeriesDiscountEntity;

class NfBookingEntity extends Entity
{
    use EntityIdTrait;
        protected ?string $cartToken;
    protected string $orderNumber;
    protected float $totalPrice;
    protected string $status;
    protected ?string $customerId;
    protected ?string $orderId;
    protected ?string $productId;
    protected ?float $originalPrice;
    protected ?float $discountAmount;
    protected ?string $discountId;
    protected float $totalCreditSlots = 0.0;

    protected float $totalCreditAmount = 0.0;
    /**
     * @var array|null
     */
    protected $creditInfo;
    protected ?NfBookingSeriesDiscountEntity $discount;
    protected ?CustomerEntity $customer;
    protected ?ProductEntity $product;
    protected ?NfBookingItemCollection $items;

    public function getCartToken(): ?string { return $this->cartToken; }
    public function setCartToken(?string $cartToken): void { $this->cartToken = $cartToken; }

    public function getOrderNumber(): string { return $this->orderNumber; }
    public function setOrderNumber(string $orderNumber): void { $this->orderNumber = $orderNumber; }

    public function getOriginalPrice(): float { return $this->originalPrice; }
    public function setOriginalPrice(float $originalPrice): void { $this->originalPrice = $originalPrice; }

    public function getDiscountAmount(): float { return $this->discountAmount; }
    public function setDiscountAmount(float $discountAmount): void { $this->discountAmount = $discountAmount; }

    public function getTotalPrice(): float { return $this->totalPrice; }
    public function setTotalPrice(float $totalPrice): void { $this->totalPrice = $totalPrice; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function getDiscountId(): ?string { return $this->discountId; }
    public function setDiscountId(?string $discountId): void { $this->discountId = $discountId; }

    public function getTotalCreditSlots(): float
    {
        return $this->totalCreditSlots;
    }

    public function setTotalCreditSlots(float $totalCreditSlots): void
    {
        $this->totalCreditSlots = $totalCreditSlots;
    }

    public function getTotalCreditAmount(): float
    {
        return $this->totalCreditAmount;
    }

    public function setTotalCreditAmount(float $totalCreditAmount): void
    {
        $this->totalCreditAmount = $totalCreditAmount;
    }

    public function getCreditInfo(): ?array
    {
        return $this->creditInfo;
    }

    public function setCreditInfo(?array $creditInfo): void
    {
        $this->creditInfo = $creditInfo;
    }

    public function getCustomerId(): ?string { return $this->customerId; }
    public function setCustomerId(?string $customerId): void { $this->customerId = $customerId; }

    public function getOrderId(): ?string { return $this->orderId; }
    public function setOrderId(?string $orderId): void { $this->orderId = $orderId; }

    public function getProductId(): ?string { return $this->productId; }
    public function setProductId(?string $productId): void { $this->productId = $productId; }

    public function getDiscount(): ?NfBookingSeriesDiscountEntity { return $this->discount; }
    public function setDiscount(?NfBookingSeriesDiscountEntity $discount): void { $this->discount = $discount; }

    public function getCustomer(): ?CustomerEntity { return $this->customer; }
    public function setCustomer(?CustomerEntity $customer): void { $this->customer = $customer; }

    public function getProduct(): ?ProductEntity
    {
        if (!isset($this->product)) {
            return null;
        }
        return $this->product;
    }
    public function setProduct(?ProductEntity $product): void { $this->product = $product; }

    public function getItems(): ?NfBookingItemCollection { return $this->items; }
    public function setItems(?NfBookingItemCollection $items): void { $this->items = $items; }
}