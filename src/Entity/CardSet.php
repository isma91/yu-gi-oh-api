<?php

namespace App\Entity;

use App\Repository\CardSetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;

#[OA\Schema(
    description: "Card Rarity/Code name of A Set for a Card, can have one or multiple CardSet."
)]
#[ORM\Entity(repositoryClass: CardSetRepository::class)]
class CardSet
{
    use TimestampableEntity;

    #[OA\Property(description: "Internal unique identifier of the CardSet", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["card_info", "set_info", "search_card"])]
    private ?int $id = null;

    #[OA\Property(
        description: "Set of the CardSet",
        type: "array",
        items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: "#/components/schemas/CardInfoCardSetSet"),
                new OA\Schema(ref: "#/components/schemas/SearchCardCardSetSet"),
            ]
        ),
    )]
    #[ORM\ManyToMany(targetEntity: Set::class, inversedBy: 'cardSets')]
    #[Groups(["card_info", "search_card"])]
    private Collection $sets;

    #[OA\Property(description: "Code of the Card name for the Set", type: "string", maxLength: 255, nullable: true)]
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["card_info", "set_info", "search_card"])]
    private ?string $code = null;

    #[OA\Property(
        description: "Rarity of the CardSet",
        type: "array",
        items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: "#/components/schemas/CardInfoCardSetRarity"),
                new OA\Schema(ref: "#/components/schemas/SetInfoCardSetRarity"),
                new OA\Schema(ref: "#/components/schemas/SearchCardCardSetRarity"),
            ]
        )
    )]
    #[ORM\ManyToMany(targetEntity: Rarity::class, inversedBy: 'cardSets')]
    #[Groups(["card_info", "set_info", "search_card"])]
    private Collection $rarities;

    #[OA\Property(
        ref: "#/components/schemas/CardInfo",
        description: "Information of the Card"
    )]
    #[ORM\ManyToOne(inversedBy: 'cardSets')]
    #[Groups(["set_info"])]
    private ?Card $card = null;

    public function __construct()
    {
        $this->sets = new ArrayCollection();
        $this->rarities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Set>
     */
    public function getSets(): Collection
    {
        return $this->sets;
    }

    public function addSet(Set $set): static
    {
        if (!$this->sets->contains($set)) {
            $this->sets->add($set);
        }

        return $this;
    }

    public function removeSet(Set $set): static
    {
        $this->sets->removeElement($set);

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return Collection<int, Rarity>
     */
    public function getRarities(): Collection
    {
        return $this->rarities;
    }

    public function addRarity(Rarity $rarity): static
    {
        if (!$this->rarities->contains($rarity)) {
            $this->rarities->add($rarity);
        }

        return $this;
    }

    public function removeRarity(Rarity $rarity): static
    {
        $this->rarities->removeElement($rarity);

        return $this;
    }

    public function getCard(): ?Card
    {
        return $this->card;
    }

    public function setCard(?Card $card): static
    {
        $this->card = $card;

        return $this;
    }
}
