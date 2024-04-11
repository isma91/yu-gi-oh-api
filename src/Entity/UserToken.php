<?php

namespace App\Entity;

use App\Repository\UserTokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserTokenRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: "deletedAt", hardDelete: true)]
class UserToken
{
    use TimestampableEntity;
    use SoftDeleteableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["user_admin_info"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["user_admin_info"])]
    private ?string $token = null;

    #[ORM\Column(type: Types::ARRAY)]
    #[Groups(["user_admin_info"])]
    private array $info = [];

    #[ORM\Column(length: 255)]
    #[Groups(["user_admin_info"])]
    private ?string $fingerprint = null;

    #[ORM\ManyToOne(inversedBy: 'userTokens')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'uuid')]
    #[Groups(["user_admin_info"])]
    private ?Uuid $uuid = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["user_admin_info"])]
    private ?\DateTimeInterface $expiratedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

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

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function setFingerprint(string $fingerprint): static
    {
        $this->fingerprint = $fingerprint;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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

    public function getExpiratedAt(): ?\DateTimeInterface
    {
        return $this->expiratedAt;
    }

    public function setExpiratedAt(\DateTimeInterface $expiratedAt): static
    {
        $this->expiratedAt = $expiratedAt;

        return $this;
    }

    #[Groups(["user_admin_info"])]
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    #[Groups(["user_admin_info"])]
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }
}
