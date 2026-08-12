<?php  declare(strict_types=1);

namespace Nf\Booking\Core\Content\BookingLocation;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class NfBookingLocationEntity extends Entity
{
    use EntityIdTrait;

    protected bool $active;

    protected string $name;

    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): void { $this->active = $active; }

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): void { $this->name = $name; }
}