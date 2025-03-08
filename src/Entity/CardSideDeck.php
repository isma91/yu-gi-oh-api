<?php

namespace App\Entity;

use App\Repository\CardSideDeckRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "A Card to the Side Deck."
)]
#[ORM\Entity(repositoryClass: CardSideDeckRepository::class)]
class CardSideDeck
{
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["deck_info"])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(["deck_info"])]
    private ?int $nbCopie = null;

    #[OA\Property(
        description: "Card from Side Deck",
        type: "array",
        items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: "#/components/schemas/CardInfo"),
            ]
        ),
        nullable: true,
    )]
    #[ORM\ManyToMany(
        targetEntity: Card::class,
        inversedBy: 'cardSideDecks',
        cascade: ['persist']
    )]
    #[Groups(["card_info"])]
    private Collection $cards;

    #[ORM\ManyToOne(inversedBy: 'cardSideDecks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Deck $deck = null;

    public function __construct()
    {
        $this->cards = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNbCopie(): ?int
    {
        return $this->nbCopie;
    }

    public function setNbCopie(int $nbCopie): static
    {
        $this->nbCopie = $nbCopie;

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

    public function getDeck(): ?Deck
    {
        return $this->deck;
    }

    public function setDeck(?Deck $deck): static
    {
        $this->deck = $deck;

        return $this;
    }
}
