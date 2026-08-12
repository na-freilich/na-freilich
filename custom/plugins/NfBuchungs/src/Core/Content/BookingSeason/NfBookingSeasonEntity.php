<?php declare(strict_types=1);

namespace Nf\Booking\Core\Content\BookingSeason;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class NfBookingSeasonEntity extends Entity
{
    use EntityIdTrait;

    protected bool $active;

    protected string $name;
    protected string $startDate;
    protected string $endDate;

    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): void { $this->active = $active; }

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): void { $this->name = $name; }

    public function getStartDate(): ?string { return $this->startDate; }
    public function setStartDate(?string $startDate): void { $this->startDate = $startDate; }

    public function getEndDate(): ?string { return $this->endDate; }
    public function setEndDate(?string $endDate): void { $this->endDate = $endDate; }
}