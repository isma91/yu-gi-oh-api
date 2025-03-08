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
    #[Groups(["search_card", "card_info", "card_random_info"])]
    private ?int $id = null;

    #[OA\Property(description: "Name of the Card", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "card_info", "card_random_info"])]
    private ?string $name = null;

    #[OA\Property(description: "Slugify name of the Card", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "card_info", "card_random_info"])]
    private ?string $slugName = null;

    #[OA\Property(description: "Uuid of the Card, true uniqueness field", type: "string", nullable: false)]
    #[ORM\Column(type: 'uuid')]
    #[Groups(["search_card", "card_info", "card_random_info"])]
    private ?Uuid $uuid = null;

    #[OA\Property(
        description: "Attribute of the Card",
        nullable: true,
        oneOf: [
            new OA\Schema(ref: "#/components/schemas/SearchCardCardAttribute"),
            new OA\Schema(ref: "#/components/schemas/CardInfoCardAttribute"),
        ]
    )]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["search_card", "card_info"])]
    private ?CardAttribute $attribute = null;

    #[OA\Property(
        description: "Property of the Card",
        nullable: true,
        oneOf: [
            new OA\Schema(ref: "#/components/schemas/SearchCardProperty"),
            new OA\Schema(ref: "#/components/schemas/CardInfoProperty"),
        ]
    )]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["search_card", "card_info"])]
    private ?Property $property = null;

    #[OA\Property(
        description: "Category of the Card",
        nullable: false,
        oneOf: [
            new OA\Schema(ref: "#/components/schemas/SearchCardCategory"),
            new OA\Schema(ref: "#/components/schemas/CardInfoCategory"),
        ]
    )]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["search_card", "card_info"])]
    private ?Category $category = null;

    #[OA\Property(
        description: "All CardPicture of the Card",
        type: "array",
        items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: "#/components/schemas/SearchCardCardPicture"),
                new OA\Schema(ref: "#/components/schemas/CardInfoCardPicture"),
            ]
        ),
    )]
    #[ORM\OneToMany(
        mappedBy: 'card',
        targetEntity: CardPicture::class,
        cascade: ['persist', 'remove']
    )]
    #[Groups(["search_card", "card_info", "card_random_info"])]
    private Collection $pictures;

    #[OA\Property(
        description: "Race of the Card",
        nullable: true,
        oneOf: [
            new OA\Schema(ref: "#/components/schemas/SearchCardType"),
            new OA\Schema(ref: "#/components/schemas/CardInfoType"),
        ]
    )]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["search_card", "card_info"])]
    private ?Type $type = null;

    #[OA\Property(
        description: "All Ability of the Card Monster",
        type: "array",
        items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: "#/components/schemas/SearchCardSubType"),
                new OA\Schema(ref: "#/components/schemas/CardInfoSubType"),
            ]
        ),
        nullable: true,
    )]
    #[ORM\ManyToMany(
        targetEntity: SubType::class,
        inversedBy: 'cards',
        cascade: ['persist']
    )]
    #[Groups(["search_card", "card_info"])]
    private Collection $subTypes;

    #[OA\Property(
        description: "If the Card Monster is an effect one, all extra-deck Monster are considered true",
        type: "boolean",
        nullable: true
    )]
    #[ORM\Column(nullable: true)]
    #[Groups(["search_card", "card_info"])]
    private ?bool $isEffect = null;

    #[OA\Property(
        description: "Complete description of the Card",
        type: "string",
        nullable: false
    )]
    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["search_card", "card_info"])]
    private ?string $description = null;

    #[OA\Property(
        description: "Attack points of the Card Monster",
        type: "integer",
        nullable: true
    )]
    #[ORM\Column(nullable: true)]
    #[Groups(["search_card", "card_info"])]
    private ?int $attackPoints = null;

    #[OA\Property(
        description: "Defense points of the Card Monster, Link Monster have 0 to avoid issue",
        type: "integer",
        nullable: true
    )]
    #[ORM\Column(nullable: true)]
    #[Groups(["search_card", "card_info"])]
    private ?int $defensePoints = null;

    #[OA\Property(
        description: "Archetype of the Card",
        nullable: true,
        oneOf: [new OA\Schema(ref: "#/components/schemas/CardInfoArchetype")]
    )]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["card_info"])]
    private ?Archetype $archetype = null;

    #[OA\Property(description: "Unique identifier from the remote api", nullable: false)]
    #[ORM\Column(nullable: false)]
    private ?int $idYGO = null;

    #[OA\Property(
        description: "CardSet of the Card",
        type: "array",
        items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: "#/components/schemas/CardInfoCardSet"),
                new OA\Schema(ref: "#/components/schemas/SearchCardCardSet")
            ]
        ),
        nullable: true,
    )]
    #[ORM\OneToMany(
        mappedBy: 'card',
        targetEntity: CardSet::class,
        cascade: ['persist', 'remove']
    )]
    #[Groups(["card_info", "search_card"])]
    private Collection $cardSets;

    #[OA\Property(
        description: "List of all Pendulum/Link specification of the Card Monster",
        type: "array",
        items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: "#/components/schemas/SearchCardSubProperty"),
                new OA\Schema(ref: "#/components/schemas/CardInfoSubProperty"),
            ]
        ),
        nullable: true
    )]
    #[ORM\ManyToMany(
        targetEntity: SubProperty::class,
        mappedBy: 'cards',
        cascade: ['persist']
    )]
    #[Groups(["search_card", "card_info"])]
    private Collection $subProperties;

    #[OA\Property(
        description: "Sub Category of the Card",
        type: "array",
        items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: "#/components/schemas/SearchCardSubCategory"),
                new OA\Schema(ref: "#/components/schemas/CardInfoSubCategory"),
            ]
        ),
        nullable: false,
    )]
    #[ORM\ManyToOne]
    #[Groups(["search_card", "card_info"])]
    private ?SubCategory $subCategory = null;

    #[OA\Property(
        description: "Slugify description Card, needed for search purpose",
        type: "string",
        nullable: false
    )]
    #[ORM\Column(type: Types::TEXT, nullable: false)]
    #[Groups(["search_card", "card_info"])]
    private ?string $slugDescription = null;

    #[OA\Property(
        description: "Pendulum effect of the Card Monster",
        type: "string",
        nullable: true
    )]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["search_card", "card_info"])]
    private ?string $pendulumDescription = null;

    #[OA\Property(
        description: "Monster description of the Pendulum Monster Card",
        type: "string",
        nullable: true
    )]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["search_card", "card_info"])]
    private ?string $monsterDescription = null;

    #[OA\Property(
        description: "If the Monster Card is a Pendulum one",
        type: "boolean",
        nullable: true
    )]
    #[ORM\Column(nullable: true)]
    #[Groups(["search_card", "card_info"])]
    private ?bool $isPendulum = null;

    #[ORM\ManyToMany(
        targetEntity: CardMainDeck::class,
        mappedBy: 'cards',
        cascade: ['persist']
    )]
    private Collection $cardMainDecks;

    #[ORM\ManyToMany(targetEntity: Deck::class, inversedBy: 'cardsUnique')]
    private Collection $decks;

    #[ORM\ManyToMany(
        targetEntity: CardExtraDeck::class,
        mappedBy: 'cards',
        cascade: ['persist']
    )]
    private Collection $cardExtraDecks;

    #[ORM\ManyToMany(
        targetEntity: CardSideDeck::class,
        mappedBy: 'cards',
        cascade: ['persist']
    )]
    private Collection $cardSideDecks;

    #[ORM\OneToMany(
        mappedBy: 'card',
        targetEntity: CardCardCollection::class,
        cascade: ['persist', 'remove']
    )]
    private Collection $cardCardCollections;

    #[ORM\Column(options: ['default' => true])]
    private bool $isMaybeOCG = TRUE;

    public function __construct()
    {
        $this->pictures = new ArrayCollection();
        $this->subTypes = new ArrayCollection();
        $this->cardSets = new ArrayCollection();
        $this->subProperties = new ArrayCollection();
        $this->cardMainDecks = new ArrayCollection();
        $this->decks = new ArrayCollection();
        $this->cardExtraDecks = new ArrayCollection();
        $this->cardSideDecks = new ArrayCollection();
        $this->cardCardCollections = new ArrayCollection();
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

    /**
     * @return Collection<int, CardMainDeck>
     */
    public function getCardMainDecks(): Collection
    {
        return $this->cardMainDecks;
    }

    public function addCardMainDeck(CardMainDeck $cardMainDeck): static
    {
        if (!$this->cardMainDecks->contains($cardMainDeck)) {
            $this->cardMainDecks->add($cardMainDeck);
            $cardMainDeck->addCard($this);
        }

        return $this;
    }

    public function removeCardMainDeck(CardMainDeck $cardMainDeck): static
    {
        if ($this->cardMainDecks->removeElement($cardMainDeck)) {
            $cardMainDeck->removeCard($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Deck>
     */
    public function getDecks(): Collection
    {
        return $this->decks;
    }

    public function addDeck(Deck $deck): static
    {
        if (!$this->decks->contains($deck)) {
            $this->decks->add($deck);
        }

        return $this;
    }

    public function removeDeck(Deck $deck): static
    {
        $this->decks->removeElement($deck);

        return $this;
    }

    /**
     * @return Collection<int, CardExtraDeck>
     */
    public function getCardExtraDecks(): Collection
    {
        return $this->cardExtraDecks;
    }

    public function addCardExtraDeck(CardExtraDeck $cardExtraDeck): static
    {
        if (!$this->cardExtraDecks->contains($cardExtraDeck)) {
            $this->cardExtraDecks->add($cardExtraDeck);
            $cardExtraDeck->addCard($this);
        }

        return $this;
    }

    public function removeCardExtraDeck(CardExtraDeck $cardExtraDeck): static
    {
        if ($this->cardExtraDecks->removeElement($cardExtraDeck)) {
            $cardExtraDeck->removeCard($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, CardSideDeck>
     */
    public function getCardSideDecks(): Collection
    {
        return $this->cardSideDecks;
    }

    public function addCardSideDeck(CardSideDeck $cardSideDeck): static
    {
        if (!$this->cardSideDecks->contains($cardSideDeck)) {
            $this->cardSideDecks->add($cardSideDeck);
            $cardSideDeck->addCard($this);
        }

        return $this;
    }

    public function removeCardSideDeck(CardSideDeck $cardSideDeck): static
    {
        if ($this->cardSideDecks->removeElement($cardSideDeck)) {
            $cardSideDeck->removeCard($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, CardCardCollection>
     */
    public function getCardCardCollections(): Collection
    {
        return $this->cardCardCollections;
    }

    public function addCardCardCollection(CardCardCollection $cardCardCollection): static
    {
        if (!$this->cardCardCollections->contains($cardCardCollection)) {
            $this->cardCardCollections->add($cardCardCollection);
            $cardCardCollection->setCard($this);
        }

        return $this;
    }

    public function removeCardCardCollection(CardCardCollection $cardCardCollection): static
    {
        if ($this->cardCardCollections->removeElement($cardCardCollection)) {
            // set the owning side to null (unless already changed)
            if ($cardCardCollection->getCard() === $this) {
                $cardCardCollection->setCard(null);
            }
        }

        return $this;
    }

    public function isIsMaybeOCG(): ?bool
    {
        return $this->isMaybeOCG;
    }

    public function setIsMaybeOCG(bool $isMaybeOCG): static
    {
        $this->isMaybeOCG = $isMaybeOCG;

        return $this;
    }
}
