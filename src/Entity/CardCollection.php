<?php

namespace App\Entity;

use App\Repository\CardCollectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "A Collection is a list of Card with more specific information."
)]
#[ORM\Entity(repositoryClass: CardCollectionRepository::class)]
class CardCollection
{
    use TimestampableEntity;
    #[OA\Property(description: "Internal unique identifier of the Collection", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["collection_info", "user_basic_info"])]
    private ?int $id = null;

    #[OA\Property(description: "Name of the Collection", type: "string", nullable: false)]
    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["collection_info", "user_basic_info"])]
    private ?string $name = null;

    #[OA\Property(description: "Slugify name of the Deck", type: "string", nullable: false)]
    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["collection_info", "user_basic_info"])]
    private ?string $slugName = null;

    #[OA\Property(description: "If we authorize other user to see the Collection", type: "boolean", nullable: false)]
    #[ORM\Column]
    #[Groups(["collection_info", "user_basic_info"])]
    private ?bool $isPublic = null;

    #[ORM\ManyToOne]
    private ?CardPicture $artwork = null;

    #[ORM\OneToMany(mappedBy: 'cardCollection', targetEntity: CardCardCollection::class)]
    private Collection $cardCardCollections;

    #[ORM\ManyToOne(inversedBy: 'cardCollections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct()
    {
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

    public function isIsPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    #[OA\Property(
        property: "artworkUrl",
        description: "URL for the Collection Artwork",
        type: "string",
        nullable: true
    )]
    #[Groups(["collection_info", "user_basic_info"])]
    public function getArtworkUrl(): ?string
    {
        return $this->artwork?->getArtworkUrl();

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

    #[OA\Property(
        property: "cardCardCollectionNumber",
        description: "Number of card in the Collection",
        type: "integer",
        nullable: false
    )]
    #[Groups(["collection_info", "user_basic_info"])]
    public function getCardCardCollectionNumber(): int
    {
        return $this->cardCardCollections->count();
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
            $cardCardCollection->setCardCollection($this);
        }

        return $this;
    }

    public function removeCardCardCollection(CardCardCollection $cardCardCollection): static
    {
        if ($this->cardCardCollections->removeElement($cardCardCollection)) {
            // set the owning side to null (unless already changed)
            if ($cardCardCollection->getCardCollection() === $this) {
                $cardCardCollection->setCardCollection(null);
            }
        }

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
}
