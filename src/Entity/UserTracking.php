<?php

namespace App\Entity;

use App\Repository\UserTrackingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserTrackingRepository::class)]
class UserTracking
{
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["user_admin_info"])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userTrackings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserToken $userToken = null;

    #[ORM\Column]
    #[Groups(["user_admin_info"])]
    private array $info = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["user_admin_info"])]
    private ?string $route = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["user_admin_info"])]
    private ?string $fingerprint = null;

    #[ORM\Column(type: 'uuid')]
    #[Groups(["user_admin_info"])]
    private ?Uuid $uuid = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserToken(): ?UserToken
    {
        return $this->userToken;
    }

    public function setUserToken(?UserToken $userToken): static
    {
        $this->userToken = $userToken;

        return $this;
    }

    public function getInfo(): array
    {
        return $this->info;
    }

    public function setInfo(array $info): static
    {
        $this->info = $info;

        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(string $route): static
    {
        $this->route = $route;

        return $this;
    }

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function setFingerprint(string $fingerprint): static
    {
        $this->fingerprint = $fingerprint;

        return $this;
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    #[Groups(["user_admin_info"])]
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    #[Groups(["user_admin_info"])]
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }
}
