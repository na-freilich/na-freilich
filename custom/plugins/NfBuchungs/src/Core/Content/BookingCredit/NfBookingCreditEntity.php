<?php declare(strict_types=1);

namespace Nf\Booking\Core\Content\BookingCredit;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class NfBookingCreditEntity extends Entity
{
    use EntityIdTrait;

    protected float $totalSlots;

    protected float $usedSlots = 0.0;

    protected bool $active;

    /**
     * @var array|null
     */
    protected $history;

    protected ?string $customerId;
    protected ?string $comment;

    protected ?CustomerEntity $customer;

    public function getTotalSlots(): float
    {
        return $this->totalSlots;
    }

    public function setTotalSlots(float $totalSlots): void
    {
        $this->totalSlots = $totalSlots;
    }

    public function getUsedSlots(): float
    {
        return $this->usedSlots;
    }

    public function setUsedSlots(float $usedSlots): void
    {
        $this->usedSlots = $usedSlots;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getHistory(): ?array
    {
        return $this->history;
    }

    public function setHistory(?array $history): void
    {
        $this->history = $history;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerId(?string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(?CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }
}