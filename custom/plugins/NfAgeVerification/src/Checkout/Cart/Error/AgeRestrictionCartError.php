<?php declare(strict_types=1);

namespace NfAgeVerification\Checkout\Cart\Error;

use Shopware\Core\Checkout\Cart\Error\Error;

class AgeRestrictionCartError extends Error
{
    public function __construct(
        protected string $lineItemId,
        protected string $name,
        protected bool $blocksOrder = false,
    ) {
        parent::__construct(
            sprintf('%s was removed from the cart because age confirmation is missing.', $name)
        );
    }

    public function getId(): string
    {
        return $this->getMessageKey() . '-' . $this->lineItemId;
    }

    public function getMessageKey(): string
    {
        return 'nfAgeRestricted';
    }

    public function getLevel(): int
    {
        return $this->blocksOrder ? self::LEVEL_ERROR : self::LEVEL_WARNING;
    }

    public function blockOrder(): bool
    {
        return $this->blocksOrder;
    }

    public function isPersistent(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return ['name' => $this->name];
    }
}
