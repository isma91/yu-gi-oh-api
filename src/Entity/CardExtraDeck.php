<?php

namespace App\Entity;

use App\Repository\CardExtraDeckRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: CardExtraDeckRepository::class)]
class CardExtraDeck
{
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $nbCopie = null;

    #[ORM\ManyToMany(targetEntity: Card::class, inversedBy: 'cardExtraDecks')]
    private Collection $cards;

    #[ORM\ManyToOne(inversedBy: 'cardExtraDecks')]
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
