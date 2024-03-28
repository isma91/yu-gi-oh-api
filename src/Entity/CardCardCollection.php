<?php

namespace App\Entity;

use App\Repository\CardCardCollectionRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: CardCardCollectionRepository::class)]
class CardCardCollection
{
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $nbCopie = null;

    #[ORM\ManyToOne(inversedBy: 'cardCardCollections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Card $card = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Set $cardSet = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Rarity $rarity = null;

    #[ORM\ManyToOne]
    private ?Country $country = null;

    #[ORM\ManyToOne(inversedBy: 'cardCardCollections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CardCollection $cardCollection = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?CardPicture $picture = null;

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

    public function getCard(): ?Card
    {
        return $this->card;
    }

    public function setCard(?Card $card): static
    {
        $this->card = $card;

        return $this;
    }

    public function getCardSet(): ?Set
    {
        return $this->cardSet;
    }

    public function setCardSet(?Set $cardSet): static
    {
        $this->cardSet = $cardSet;

        return $this;
    }

    public function getRarity(): ?Rarity
    {
        return $this->rarity;
    }

    public function setRarity(?Rarity $rarity): static
    {
        $this->rarity = $rarity;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getCardCollection(): ?CardCollection
    {
        return $this->cardCollection;
    }

    public function setCardCollection(?CardCollection $cardCollection): static
    {
        $this->cardCollection = $cardCollection;

        return $this;
    }

    public function getPicture(): ?CardPicture
    {
        return $this->picture;
    }

    public function setPicture(?CardPicture $picture): static
    {
        $this->picture = $picture;

        return $this;
    }
}
