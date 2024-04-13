<?php

namespace App\Entity;

use App\Repository\UserTokenRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    #[Groups(["user_admin_info", "user_token_info"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["user_admin_info"])]
    private ?string $token = null;

    #[ORM\ManyToOne(inversedBy: 'userTokens')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["user_admin_info", "user_token_info"])]
    private ?\DateTimeInterface $expiratedAt = null;

    #[ORM\OneToMany(mappedBy: 'userToken', targetEntity: UserTracking::class)]
    #[Groups(["user_admin_info"])]
    private Collection $userTrackings;

    #[ORM\Column]
    #[Groups(["user_admin_info", "user_token_info"])]
    private ?int $nbUsage = 0;

    public function __construct()
    {
        $this->userTrackings = new ArrayCollection();
    }

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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

    #[Groups(["user_admin_info"])]
    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

    /**
     * @return Collection<int, UserTracking>
     */
    public function getUserTrackings(): Collection
    {
        return $this->userTrackings;
    }

    public function addUserTracking(UserTracking $userTracking): static
    {
        if (!$this->userTrackings->contains($userTracking)) {
            $this->userTrackings->add($userTracking);
            $userTracking->setUserToken($this);
        }

        return $this;
    }

    public function removeUserTracking(UserTracking $userTracking): static
    {
        if ($this->userTrackings->removeElement($userTracking)) {
            // set the owning side to null (unless already changed)
            if ($userTracking->getUserToken() === $this) {
                $userTracking->setUserToken(null);
            }
        }

        return $this;
    }

    public function getNbUsage(): ?int
    {
        return $this->nbUsage;
    }

    public function setNbUsage(int $nbUsage): static
    {
        $this->nbUsage = $nbUsage;

        return $this;
    }

    public function incrementNbUsage(): static
    {
        $this->nbUsage++;
        return $this;
    }
}
