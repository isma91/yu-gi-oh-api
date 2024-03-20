<?php

namespace App\Entity;

use App\Repository\DeckRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "A Deck is a list of Card with some specification."
)]
#[ORM\Entity(repositoryClass: DeckRepository::class)]
class Deck
{
    use TimestampableEntity;
    #[OA\Property(description: "Internal unique identifier of the Deck", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["deck_user_list", "deck_info"])]
    private ?int $id = null;

    #[OA\Property(description: "Name of the Deck", type: "string", nullable: false)]
    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["deck_user_list", "deck_info"])]
    private ?string $name = null;

    #[OA\Property(description: "Slugify name of the Deck", type: "string", nullable: false)]
    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["deck_user_list", "deck_info"])]
    private ?string $slugName = null;

    #[OA\Property(description: "If we authorize other user to see the Deck", type: "boolean", nullable: false)]
    #[ORM\Column]
    #[Groups(["deck_user_list", "deck_info"])]
    private ?bool $isPublic = null;

    #[ORM\ManyToOne(inversedBy: 'decks')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["deck_user_list", "deck_info"])]
    private ?User $user = null;

    #[ORM\ManyToMany(targetEntity: Card::class, mappedBy: 'decks')]
    private Collection $cardsUnique;

    #[OA\Property(
        description: "All Card from Main Deck",
        type: "array",
        items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: "#/components/schemas/DeckInfoCardMainDeck"),
            ]
        ),
        nullable: true,
    )]
    #[ORM\OneToMany(mappedBy: 'deck', targetEntity: CardMainDeck::class)]
    #[Groups(["deck_info"])]
    private Collection $cardMainDecks;

    #[OA\Property(
        description: "All Card from Extra Deck",
        type: "array",
        items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: "#/components/schemas/DeckInfoCardExtraDeck"),
            ]
        ),
        nullable: true,
    )]
    #[ORM\OneToMany(mappedBy: 'deck', targetEntity: CardExtraDeck::class)]
    #[Groups(["deck_info"])]
    private Collection $cardExtraDecks;

    #[OA\Property(
        description: "All Card from Side Deck",
        type: "array",
        items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: "#/components/schemas/DeckInfoCardSideDeck"),
            ]
        ),
        nullable: true,
    )]
    #[ORM\OneToMany(mappedBy: 'deck', targetEntity: CardSideDeck::class)]
    #[Groups(["deck_info"])]
    private Collection $cardSideDecks;

    #[ORM\ManyToOne]
    private ?CardPicture $artwork = null;

    public function __construct()
    {
        $this->cardsUnique = new ArrayCollection();
        $this->cardMainDecks = new ArrayCollection();
        $this->cardExtraDecks = new ArrayCollection();
        $this->cardSideDecks = new ArrayCollection();
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

    public function isIsPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    #[OA\Property(
        property: "cardUniqueNumber",
        description: "Total card number unique in the Deck",
        type: "integer",
        nullable: false
    )]
    #[Groups(["deck_user_list"])]
    public function getCardUniqueNumber(): int
    {
        return $this->cardsUnique->count();
    }

    /**
     * @return Collection<int, Card>
     */
    public function getCardsUnique(): Collection
    {
        return $this->cardsUnique;
    }

    public function addCardsUnique(Card $cardsUnique): static
    {
        if (!$this->cardsUnique->contains($cardsUnique)) {
            $this->cardsUnique->add($cardsUnique);
            $cardsUnique->addDeck($this);
        }

        return $this;
    }

    public function removeCardsUnique(Card $cardsUnique): static
    {
        if ($this->cardsUnique->removeElement($cardsUnique)) {
            $cardsUnique->removeDeck($this);
        }

        return $this;
    }

    #[OA\Property(
        property: "cardMainDeckNumber",
        description: "Total card number in the main-deck",
        type: "integer",
        nullable: false
    )]
    #[Groups(["deck_user_list"])]
    public function getCardMainDeckNumber(): int
    {
        return $this->cardMainDecks->count();
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
            $cardMainDeck->setDeck($this);
        }

        return $this;
    }

    public function removeCardMainDeck(CardMainDeck $cardMainDeck): static
    {
        if ($this->cardMainDecks->removeElement($cardMainDeck)) {
            // set the owning side to null (unless already changed)
            if ($cardMainDeck->getDeck() === $this) {
                $cardMainDeck->setDeck(null);
            }
        }

        return $this;
    }

    #[OA\Property(
        property: "cardExtraDeckNumber",
        description: "Total card number in the extra-deck",
        type: "integer",
        nullable: false
    )]
    #[Groups(["deck_user_list"])]
    public function getCardExtraDeckNumber(): int
    {
        return $this->cardExtraDecks->count();
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
            $cardExtraDeck->setDeck($this);
        }

        return $this;
    }

    public function removeCardExtraDeck(CardExtraDeck $cardExtraDeck): static
    {
        if ($this->cardExtraDecks->removeElement($cardExtraDeck)) {
            // set the owning side to null (unless already changed)
            if ($cardExtraDeck->getDeck() === $this) {
                $cardExtraDeck->setDeck(null);
            }
        }

        return $this;
    }

    #[OA\Property(
        property: "cardSideDeckNumber",
        description: "Total card number in the side-deck",
        type: "integer",
        nullable: false
    )]
    #[Groups(["deck_user_list"])]
    public function getCardSideDeckNumber(): int
    {
        return $this->cardSideDecks->count();
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
            $cardSideDeck->setDeck($this);
        }

        return $this;
    }

    public function removeCardSideDeck(CardSideDeck $cardSideDeck): static
    {
        if ($this->cardSideDecks->removeElement($cardSideDeck)) {
            // set the owning side to null (unless already changed)
            if ($cardSideDeck->getDeck() === $this) {
                $cardSideDeck->setDeck(null);
            }
        }

        return $this;
    }

    #[OA\Property(
        property: "artworkUrl",
        description: "URL for the Deck Artwork",
        type: "string",
        nullable: true
    )]
    #[Groups(["deck_user_list", "deck_info"])]
    public function getArtworkUrl(): ?string
    {
        return $this->artwork?->getArtworkUrl();

    }

    #[OA\Property(
        property: "artworkCardId",
        description: "Card Id of the CardPicture",
        type: "integer",
        nullable: true
    )]
    #[Groups(["deck_info"])]
    public function getArtworkCardId(): ?int
    {
        return $this->artwork?->getCard()?->getId();
    }

    public function getArtwork(): ?CardPicture
    {
        return $this->artwork;
    }

    public function setArtwork(?CardPicture $artwork): static
    {
        $this->artwork = $artwork;

        return $this;
    }
}
