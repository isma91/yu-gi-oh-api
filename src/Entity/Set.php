<?php

namespace App\Entity;

use App\Repository\SetRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "Set is a release of multiple Card at once."
)]
#[ORM\Entity(repositoryClass: SetRepository::class)]
#[ORM\Table(name: '`set`')]
class Set
{
    use TimestampableEntity;

    #[OA\Property(description: "Internal unique identifier of the Set", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["card_info", "set_search"])]
    private ?int $id = null;

    #[OA\Property(
        description: "Code name of the Set, can be a duplicate",
        type: "string",
        maxLength: 255,
        nullable: false
    )]
    #[ORM\Column(length: 255)]
    #[Groups(["card_info", "set_search"])]
    private ?string $code = null;

    #[OA\Property(description: "Name of the Set, always unique", type: "string", nullable: false)]
    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["card_info", "set_search"])]
    private ?string $name = null;

    #[OA\Property(description: "Slugify name of the Set", type: "string", nullable: false)]
    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["card_info", "set_search"])]
    private ?string $slugName = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["set_search"])]
    private ?int $nbCard = null;

    #[ORM\ManyToMany(targetEntity: CardSet::class, mappedBy: 'sets')]
    private Collection $cardSets;

    #[OA\Property(description: "Release date of the Set", type: "string", format: "date-time", nullable: true)]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(["card_info", "set_search"])]
    private ?DateTimeInterface $releaseDate = null;

    public function __construct()
    {
        $this->cardSets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlugName(): ?string
    {
        return $this->slugName;
    }

    public function setSlugName(string $slugName): static
    {
        $this->slugName = $slugName;

        return $this;
    }

    public function getNbCard(): ?int
    {
        return $this->nbCard;
    }

    public function setNbCard(?int $nbCard): static
    {
        $this->nbCard = $nbCard;

        return $this;
    }

    /**
     * @return Collection<int, CardSet>
     */
    public function getCardSets(): Collection
    {
        return $this->cardSets;
    }

    public function addCardSet(CardSet $cardSet): static
    {
        if (!$this->cardSets->contains($cardSet)) {
            $this->cardSets->add($cardSet);
            $cardSet->addSet($this);
        }

        return $this;
    }

    public function removeCardSet(CardSet $cardSet): static
    {
        if ($this->cardSets->removeElement($cardSet)) {
            $cardSet->removeSet($this);
        }

        return $this;
    }

    public function getReleaseDate(): ?DateTimeInterface
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?DateTimeInterface $releaseDate): static
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }
}
