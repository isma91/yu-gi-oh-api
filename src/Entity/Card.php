<?php

namespace App\Entity;

use App\Repository\CardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;


#[ORM\Entity(repositoryClass: CardRepository::class)]
class Card
{
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $slugName = null;

    #[ORM\Column(type: 'uuid')]
    private ?Uuid $uuid = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?CardAttribute $attribute = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Property $property = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\OneToMany(mappedBy: 'card', targetEntity: CardPicture::class)]
    private Collection $pictures;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Type $type = null;

    #[ORM\ManyToMany(targetEntity: SubType::class, inversedBy: 'cards')]
    private Collection $subTypes;

    #[ORM\Column(nullable: true)]
    private ?bool $isEffect = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $attackPoints = null;

    #[ORM\Column(nullable: true)]
    private ?int $defensePoints = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Archetype $archetype = null;

    #[ORM\Column(nullable: false)]
    private ?int $idYGO = null;

    #[ORM\OneToMany(mappedBy: 'card', targetEntity: CardSet::class)]
    private Collection $cardSets;

    #[ORM\ManyToMany(targetEntity: SubProperty::class, mappedBy: 'cards')]
    private Collection $subProperties;

    #[ORM\ManyToOne]
    private ?SubCategory $subCategory = null;

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
}
