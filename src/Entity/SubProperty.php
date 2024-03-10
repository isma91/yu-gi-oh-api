<?php

namespace App\Entity;

use App\Repository\SubPropertyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SubPropertyRepository::class)]
class SubProperty
{
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["search_card", "sub_property_type_list"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "sub_property_type_list"])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "sub_property_type_list"])]
    private ?string $slugName = null;

    #[ORM\ManyToOne(inversedBy: 'subProperties')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["search_card"])]
    private ?SubPropertyType $subPropertyType = null;

    #[ORM\ManyToMany(targetEntity: Card::class, inversedBy: 'subProperties')]
    private Collection $cards;

    public function __construct()
    {
        $this->cards = new ArrayCollection();
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

    public function getSubPropertyType(): ?SubPropertyType
    {
        return $this->subPropertyType;
    }

    public function setSubPropertyType(?SubPropertyType $subPropertyType): static
    {
        $this->subPropertyType = $subPropertyType;

        return $this;
    }

    /**
     * @return Collection<int, Card>
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    public function addCard(Card $card): static
    {
        if (!$this->cards->contains($card)) {
            $this->cards->add($card);
        }

        return $this;
    }

    public function removeCard(Card $card): static
    {
        $this->cards->removeElement($card);

        return $this;
    }
}
