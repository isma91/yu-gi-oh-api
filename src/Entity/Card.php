<?php

namespace App\Entity;

use App\Repository\CardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "The Card Entity, all info is stored in this entity."
)]
#[ORM\Entity(repositoryClass: CardRepository::class)]
class Card
{
    use TimestampableEntity;

    #[OA\Property(description: "Internal unique identifier of the Card", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["search_card"])]
    private ?int $id = null;

    #[OA\Property(description: "Name of the Card", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["search_card"])]
    private ?string $name = null;

    #[OA\Property(description: "Slugify name of the Card", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["search_card"])]
    private ?string $slugName = null;

    #[OA\Property(description: "Uuid of the Card, true uniqueness field", type: "string", nullable: false)]
    #[ORM\Column(type: 'uuid')]
    #[Groups(["search_card"])]
    private ?Uuid $uuid = null;

    #[OA\Property(
        description: "Attribute of the Card",
        nullable: true,
        oneOf: [new OA\Schema(ref: "#/components/schemas/SearchCardCardAttribute")]
    )]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["search_card"])]
    private ?CardAttribute $attribute = null;

    #[OA\Property(description: "Property of the Card", nullable: true)]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["search_card"])]
    private ?Property $property = null;

    #[OA\Property(
        description: "Category of the Card",
        nullable: true,
        oneOf: [new OA\Schema(ref: "#/components/schemas/SearchCardCategory")]
    )]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["search_card"])]
    private ?Category $category = null;

    #[OA\Property(
        description: "All CardPicture of the Card",
        type: "array",
        items: new OA\Items(ref: "#/components/schemas/SearchCardCardPicture"),
        nullable: true
    )]
    #[ORM\OneToMany(mappedBy: 'card', targetEntity: CardPicture::class)]
    #[Groups(["search_card"])]
    private Collection $pictures;

    #[OA\Property(
        description: "Race of the Card",
        nullable: true,
        oneOf: [new OA\Schema(ref: "#/components/schemas/SearchCardType")]
    )]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["search_card"])]
    private ?Type $type = null;

    #[OA\Property(
        description: "All Ability of the Card Monster",
        type: "array",
        items: new OA\Items(ref: "#/components/schemas/SearchCardSubType"),
        nullable: true
    )]
    #[ORM\ManyToMany(targetEntity: SubType::class, inversedBy: 'cards')]
    #[Groups(["search_card"])]
    private Collection $subTypes;

    #[OA\Property(
        description: "If the Card Monster is an effect one, all extra-deck Monster are considered true",
        type: "boolean",
        nullable: true
    )]
    #[ORM\Column(nullable: true)]
    #[Groups(["search_card"])]
    private ?bool $isEffect = null;

    #[OA\Property(
        description: "Complete description of the Card",
        type: "string",
        nullable: false
    )]
    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["search_card"])]
    private ?string $description = null;

    #[OA\Property(
        description: "Attack points of the Card Monster",
        type: "integer",
        nullable: true
    )]
    #[ORM\Column(nullable: true)]
    #[Groups(["search_card"])]
    private ?int $attackPoints = null;

    #[OA\Property(
        description: "Defense points of the Card Monster, Link Monster have 0 to avoid issue",
        type: "integer",
        nullable: true
    )]
    #[ORM\Column(nullable: true)]
    #[Groups(["search_card"])]
    private ?int $defensePoints = null;

    #[OA\Property(
        description: "Archetype of the Card",
        nullable: true,
        oneOf: [new OA\Schema(ref: "#/components/schemas/SearchCardArchetype")]
    )]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Archetype $archetype = null;

    #[OA\Property(description: "Unique identifier from the remote api", nullable: false)]
    #[ORM\Column(nullable: false)]
    private ?int $idYGO = null;

    #[ORM\OneToMany(mappedBy: 'card', targetEntity: CardSet::class)]
    private Collection $cardSets;

    #[OA\Property(
        description: "List of all Pendulum/Link specification of the Card Monster",
        type: "array",
        items: new OA\Items(ref: "#/components/schemas/SearchCardSubProperty"),
        nullable: true
    )]
    #[ORM\ManyToMany(targetEntity: SubProperty::class, mappedBy: 'cards')]
    #[Groups(["search_card"])]
    private Collection $subProperties;

    #[OA\Property(
        description: "Sub Category of the Card",
        nullable: true,
        oneOf: [new OA\Schema(ref: "#/components/schemas/SearchCardSubCategory")]
    )]
    #[ORM\ManyToOne]
    #[Groups(["search_card"])]
    private ?SubCategory $subCategory = null;

    #[OA\Property(
        description: "Slugify description Card, needed for search purpose",
        type: "string",
        nullable: false
    )]
    #[ORM\Column(type: Types::TEXT, nullable: false)]
    #[Groups(["search_card"])]
    private ?string $slugDescription = null;

    #[OA\Property(
        description: "Pendulum effect of the Card Monster",
        type: "string",
        nullable: true
    )]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["search_card"])]
    private ?string $pendulumDescription = null;

    #[OA\Property(
        description: "Monster description of the Pendulum Monster Card",
        type: "string",
        nullable: true
    )]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["search_card"])]
    private ?string $monsterDescription = null;

    #[OA\Property(
        description: "If the Monster Card is a Pendulum one",
        type: "boolean",
        nullable: true
    )]
    #[ORM\Column(nullable: true)]
    #[Groups(["search_card"])]
    private ?bool $isPendulum = null;

    public function __construct()
    {
        $this->pictures = new ArrayCollection();
        $this->subTypes = new ArrayCollection();
        $this->cardSets = new ArrayCollection();
        $this->subProperties = new ArrayCollection();
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

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getAttribute(): ?CardAttribute
    {
        return $this->attribute;
    }

    public function setAttribute(?CardAttribute $attribute): static
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function getProperty(): ?Property
    {
        return $this->property;
    }

    public function setProperty(?Property $property): static
    {
        $this->property = $property;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, CardPicture>
     */
    public function getPictures(): Collection
    {
        return $this->pictures;
    }

    public function addPicture(CardPicture $picture): static
    {
        if (!$this->pictures->contains($picture)) {
            $this->pictures->add($picture);
            $picture->setCard($this);
        }

        return $this;
    }

    public function removePicture(CardPicture $picture): static
    {
        if ($this->pictures->removeElement($picture)) {
            // set the owning side to null (unless already changed)
            if ($picture->getCard() === $this) {
                $picture->setCard(null);
            }
        }

        return $this;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    public function setType(?Type $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, SubType>
     */
    public function getSubTypes(): Collection
    {
        return $this->subTypes;
    }

    public function addSubType(SubType $subType): static
    {
        if (!$this->subTypes->contains($subType)) {
            $this->subTypes->add($subType);
        }

        return $this;
    }

    public function removeSubType(SubType $subType): static
    {
        $this->subTypes->removeElement($subType);

        return $this;
    }

    public function isIsEffect(): ?bool
    {
        return $this->isEffect;
    }

    public function setIsEffect(?bool $isEffect): static
    {
        $this->isEffect = $isEffect;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAttackPoints(): ?int
    {
        return $this->attackPoints;
    }

    public function setAttackPoints(?int $attackPoints): static
    {
        $this->attackPoints = $attackPoints;

        return $this;
    }

    public function getDefensePoints(): ?int
    {
        return $this->defensePoints;
    }

    public function setDefensePoints(?int $defensePoints): static
    {
        $this->defensePoints = $defensePoints;

        return $this;
    }

    public function getArchetype(): ?Archetype
    {
        return $this->archetype;
    }

    public function setArchetype(?Archetype $archetype): static
    {
        $this->archetype = $archetype;

        return $this;
    }

    public function getIdYGO(): ?int
    {
        return $this->idYGO;
    }

    public function setIdYGO(int $idYGO): static
    {
        $this->idYGO = $idYGO;

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
            $cardSet->setCard($this);
        }

        return $this;
    }

    public function removeCardSet(CardSet $cardSet): static
    {
        if ($this->cardSets->removeElement($cardSet)) {
            // set the owning side to null (unless already changed)
            if ($cardSet->getCard() === $this) {
                $cardSet->setCard(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SubProperty>
     */
    public function getSubProperties(): Collection
    {
        return $this->subProperties;
    }

    public function addSubProperty(SubProperty $subProperty): static
    {
        if (!$this->subProperties->contains($subProperty)) {
            $this->subProperties->add($subProperty);
            $subProperty->addCard($this);
        }

        return $this;
    }

    public function removeSubProperty(SubProperty $subProperty): static
    {
        if ($this->subProperties->removeElement($subProperty)) {
            $subProperty->removeCard($this);
        }

        return $this;
    }

    public function getSubCategory(): ?SubCategory
    {
        return $this->subCategory;
    }

    public function setSubCategory(?SubCategory $subCategory): static
    {
        $this->subCategory = $subCategory;

        return $this;
    }

    public function getSlugDescription(): ?string
    {
        return $this->slugDescription;
    }

    public function setSlugDescription(string $slugDescription): static
    {
        $this->slugDescription = $slugDescription;

        return $this;
    }

    public function getPendulumDescription(): ?string
    {
        return $this->pendulumDescription;
    }

    public function setPendulumDescription(?string $pendulumDescription): static
    {
        $this->pendulumDescription = $pendulumDescription;

        return $this;
    }

    public function getMonsterDescription(): ?string
    {
        return $this->monsterDescription;
    }

    public function setMonsterDescription(?string $monsterDescription): static
    {
        $this->monsterDescription = $monsterDescription;

        return $this;
    }

    public function isIsPendulum(): ?bool
    {
        return $this->isPendulum;
    }

    public function setIsPendulum(?bool $isPendulum): static
    {
        $this->isPendulum = $isPendulum;

        return $this;
    }
}
