<?php

namespace Nf\Booking\Core\Content\Booking\BookingItem;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Nf\Booking\Core\Content\Booking\NfBookingEntity;
use Nf\Booking\Core\Content\BookingLocation\NfBookingLocationEntity;
class NfBookingItemEntity extends Entity
{
    use EntityIdTrait;

    protected string $bookingId;
    protected ?NfBookingEntity $booking;
    protected string $locationId;
    protected ?NfBookingLocationEntity $location;
    protected \DateTimeInterface $bookingDate;
    protected string $startTime;
    protected string $endTime;
    protected float $unitPrice;

    public function getBookingId(): string { return $this->bookingId; }
    public function setBookingId(string $bookingId): void { $this->bookingId = $bookingId; }

    public function getBooking(): ?NfBookingEntity { return $this->booking; }
    public function setBooking(?NfBookingEntity $booking): void { $this->booking = $booking; }

    public function getLocationId(): string { return $this->locationId; }
    public function setLocationId(string $locationId): void { $this->locationId = $locationId; }

    public function getLocation(): ?NfBookingLocationEntity { return $this->location; }
    public function setLocation(?NfBookingLocationEntity $location): void { $this->location = $location; }

    public function getBookingDate(): \DateTimeInterface { return $this->bookingDate; }
    public function setBookingDate(\DateTimeInterface $bookingDate): void { $this->bookingDate = $bookingDate; }

    public function getStartTime(): string { return $this->startTime; }
    public function setStartTime(string $startTime): void { $this->startTime = $startTime; }

    public function getEndTime(): string { return $this->endTime; }
    public function setEndTime(string $endTime): void { $this->endTime = $endTime; }

    public function getUnitPrice(): float { return $this->unitPrice; }
    public function setUnitPrice(float $unitPrice): void { $this->unitPrice = $unitPrice; }
}