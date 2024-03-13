<?php

namespace App\Entity;

use App\Repository\SubPropertyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "The Pendulum scale or Link Arrow of the Card Monster. A Card can have one, multiple or not a SubProperty."
)]
#[ORM\Entity(repositoryClass: SubPropertyRepository::class)]
class SubProperty
{
    use TimestampableEntity;

    #[OA\Property(description: "Internal unique identifier of the SubProperty", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["search_card", "sub_property_type_list", "card_info"])]
    private ?int $id = null;

    #[OA\Property(description: "Name of the SubProperty", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "sub_property_type_list", "card_info"])]
    private ?string $name = null;

    #[OA\Property(description: "Slugify name of the SubProperty", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "sub_property_type_list", "card_info"])]
    private ?string $slugName = null;

    #[OA\Property(
        description: "The Pendulum/Link specific requirement of a Card Monster",
        type: "array",
        items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: "#/components/schemas/SearchCardSubPropertyType"),
                new OA\Schema(ref: "#/components/schemas/CardInfoSubPropertyType"),
            ]
        ),
        nullable: false
    )]
    #[ORM\ManyToOne(inversedBy: 'subProperties')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["search_card", "card_info"])]
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
