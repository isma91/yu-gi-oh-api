<?php

namespace App\Entity;

use App\Repository\SubTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "The Ability of a Card Monster. A Card can have one, multiple or not a SubType."
)]
#[ORM\Entity(repositoryClass: SubTypeRepository::class)]
class SubType
{
    use TimestampableEntity;

    #[OA\Property(description: "Internal unique identifier of the SubType", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["search_card", "sub_type_list", "card_info"])]
    private ?int $id = null;

    #[OA\Property(description: "Name of the SubType", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "sub_type_list", "card_info"])]
    private ?string $name = null;

    #[OA\Property(description: "Slugify name of the SubType", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "sub_type_list", "card_info"])]
    private ?string $slugName = null;

    #[ORM\ManyToMany(
        targetEntity: Card::class,
        mappedBy: 'subTypes',
        cascade: ['persist']
    )]
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
            $card->addSubType($this);
        }

        return $this;
    }

    public function removeCard(Card $card): static
    {
        if ($this->cards->removeElement($card)) {
            $card->removeSubType($this);
        }

        return $this;
    }
}
