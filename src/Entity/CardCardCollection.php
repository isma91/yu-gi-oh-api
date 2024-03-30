<?php

namespace App\Entity;

use App\Repository\CardCardCollectionRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "A CardCollection is a the card info of CardCollection."
)]
#[ORM\Entity(repositoryClass: CardCardCollectionRepository::class)]
class CardCardCollection
{
    use TimestampableEntity;
    #[OA\Property(description: "Internal unique identifier of the CardCollection", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["collection_info"])]
    private ?int $id = null;

    #[OA\Property(description: "Number of copie of the Card", type: "integer", nullable: false)]
    #[ORM\Column]
    #[Groups(["collection_info"])]
    private ?int $nbCopie = null;

    #[OA\Property(
        property: "card",
        ref: "#/components/schemas/SearchCardList",
        description: "Card info",
        nullable: false
    )]
    #[ORM\ManyToOne(inversedBy: 'cardCardCollections')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups("collection_info")]
    private ?Card $card = null;

    #[OA\Property(
        property: "cardSet",
        ref: "#/components/schemas/CardCollectionInfoCardCardCollectionSet",
        description: "Set of the Card",
        nullable: true
    )]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["collection_info"])]
    private ?Set $cardSet = null;

    #[OA\Property(
        property: "rarity",
        ref: "#/components/schemas/CardCollectionInfoCardCardCollectionRarity",
        description: "Rarity of the Card",
        nullable: true
    )]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["collection_info"])]
    private ?Rarity $rarity = null;

    #[OA\Property(
        property: "country",
        ref: "#/components/schemas/CountryList",
        description: "Language of the Card",
        nullable: false
    )]
    #[ORM\ManyToOne]
    #[Groups(["collection_info"])]
    private ?Country $country = null;

    #[ORM\ManyToOne(inversedBy: 'cardCardCollections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CardCollection $cardCollection = null;

    #[OA\Property(
        property: "picture",
        ref: "#/components/schemas/CardCollectionInfoCardCardCollectionCardPicture",
        description: "CardPicture of the Card",
        nullable: false
    )]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["collection_info"])]
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
