<?php declare(strict_types=1);

namespace Nf\Booking\Core\Content\BookingSeriesDiscount;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class NfBookingSeriesDiscountEntity extends Entity
{
    use EntityIdTrait;

    protected bool $active;
    protected int $minCount;
    protected float $discountPercentage;

    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): void { $this->active = $active; }

    public function getMinCount(): int { return $this->minCount; }
    public function setMinCount(int $minCount): void { $this->minCount = $minCount; }

    public function getDiscountPercentage(): float { return $this->discountPercentage; }
    public function setDiscountPercentage(float $discountPercentage): void { $this->discountPercentage = $discountPercentage; }
}