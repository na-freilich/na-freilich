<?php declare(strict_types=1);

namespace Nf\Booking\Core\Content\BookingLocation\Aggregate;

use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;
use Nf\Booking\Core\Content\BookingLocation\NfBookingLocationEntity;

class NfBookingLocationTranslationEntity extends TranslationEntity
{
    protected string $nfBookingLocationId;

    protected ?string $name;

    protected ?NfBookingLocationEntity $nfBookingLocation;

    public function getNfBookingLocationId(): string { return $this->nfBookingLocationId; }
    public function setNfBookingLocationId(string $id): void { $this->nfBookingLocationId = $id; }

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): void { $this->name = $name; }

    public function getNfBookingLocation(): ?NfBookingLocationEntity { return $this->nfBookingLocation; }
    public function setNfBookingLocation(?NfBookingLocationEntity $location): void { $this->nfBookingLocation = $location; }
}