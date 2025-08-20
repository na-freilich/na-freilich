<?php declare(strict_types=1);

namespace Nf\Statistics\Core\Content\StatCurrentUsers;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class StatCurrentUsersEntity extends Entity
{
    use EntityIdTrait;

    protected string $remoteAddr;

    protected string $page;

    protected ?string $userId;

    protected string $token;

    protected string $deviceType;

    public function getRemoteAddr(): string
    {
        return $this->remoteAddr;
    }

    public function setRemoteAddr(string $remoteAddr): void
    {
        $this->remoteAddr = $remoteAddr;
    }

    public function getPage(): string
    {
        return $this->page;
    }

    public function setPage(string $page): void
    {
        $this->page = $page;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getDeviceType(): ?string
    {
        return $this->deviceType;
    }

    public function setDeviceType(string $deviceType): void
    {
        $this->deviceType = $deviceType;
    }

}