<?php

namespace App\Entity;

use App\Repository\CardSetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: CardSetRepository::class)]
class CardSet
{
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToMany(targetEntity: Set::class, inversedBy: 'cardSets')]
    private Collection $sets;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\ManyToMany(targetEntity: Rarity::class, inversedBy: 'cardSets')]
    private Collection $rarities;

    #[ORM\ManyToOne(inversedBy: 'cardSets')]
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
