<?php

namespace App\Entity;

use App\Repository\RarityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "Rarity of the Card."
)]
#[ORM\Entity(repositoryClass: RarityRepository::class)]
class Rarity
{
    use TimestampableEntity;

    #[OA\Property(description: "Internal unique identifier of the Rarity", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["card_info", "set_info", "search_card"])]
    private ?int $id = null;

    #[OA\Property(description: "Name of the Rarity", type: "string", nullable: false)]
    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["card_info", "set_info", "search_card"])]
    private ?string $name = null;

    #[OA\Property(description: "Slugify name of the Rarity", type: "string", nullable: false)]
    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["card_info", "set_info", "search_card"])]
    private ?string $slugName = null;

    #[ORM\ManyToMany(targetEntity: CardSet::class, mappedBy: 'rarities')]
    private Collection $cardSets;

    public function __construct()
    {
        $this->cardSets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
            $cardSet->addRarity($this);
        }

        return $this;
    }

    public function removeCardSet(CardSet $cardSet): static
    {
        if ($this->cardSets->removeElement($cardSet)) {
            $cardSet->removeRarity($this);
        }

        return $this;
    }
}
