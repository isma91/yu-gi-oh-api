<?php

namespace App\Entity;

use App\Repository\CardPictureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "A Card can have many CardPicture."
)]
#[ORM\Entity(repositoryClass: CardPictureRepository::class)]
class CardPicture
{
    private const CARD_PICTURE_ROUTE_BASE = "card-picture";
    use TimestampableEntity;

    #[OA\Property(description: "Internal unique identifier of the CardPicture", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["search_card", "card_info"])]
    private ?int $id = null;

    #[OA\Property(description: "file name of the classic picture", type: "string", nullable: true)]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $picture = null;

    #[OA\Property(description: "file name of the picture cropped", type: "string", nullable: true)]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pictureSmall = null;

    #[OA\Property(description: "file name of the artwork only", type: "string", nullable: true)]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $artwork = null;

    #[ORM\ManyToOne(inversedBy: 'pictures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Card $card = null;

    #[OA\Property(description: "Unique identifier from remote api", type: "integer", nullable: false)]
    #[ORM\Column]
    private ?int $idYGO = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param string|null $string
     * @return string|null
     */
    private function _getUrl(?string $string): ?string
    {
        $url = NULL;
        $cardEntity = $this->card;
        $pictureIdYGO = $this->idYGO;
        if ($string !== NULL && $cardEntity !== NULL && $pictureIdYGO !== NULL) {
            $cardEntityUuid = $cardEntity->getUuid();
            if ($cardEntityUuid === NULL) {
                return NULL;
            }
            $url = sprintf(
                "/%s/%s/%s/%s/%s",
                self::CARD_PICTURE_ROUTE_BASE,
                "display",
                $cardEntityUuid->__toString(),
                $pictureIdYGO,
                $string
            );
        }
        return $url;
    }

    #[OA\Property(description: "get url for the classic picture", type: "string", nullable: true)]
    #[Groups(["card_info", "card_random_info"])]
    public function getPictureUrl(): ?string
    {
        return $this->_getUrl($this->picture);
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): static
    {
        $this->picture = $picture;

        return $this;
    }

    #[OA\Property(description: "get url for the small picture", type: "string", nullable: true)]
    #[Groups(["search_card", "card_info"])]
    public function getPictureSmallUrl(): ?string
    {
        return $this->_getUrl($this->pictureSmall);
    }

    public function getPictureSmall(): ?string
    {
        return $this->pictureSmall;
    }

    public function setPictureSmall(?string $pictureSmall): static
    {
        $this->pictureSmall = $pictureSmall;

        return $this;
    }

    #[OA\Property(description: "get url for the artwork", type: "string", nullable: true)]
    #[Groups(["card_info"])]
    public function getArtworkUrl(): ?string
    {
        return $this->_getUrl($this->artwork);
    }

    public function getArtwork(): ?string
    {
        return $this->artwork;
    }

    public function setArtwork(?string $artwork): static
    {
        $this->artwork = $artwork;

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

    public function getIdYGO(): ?int
    {
        return $this->idYGO;
    }

    public function setIdYGO(int $idYGO): static
    {
        $this->idYGO = $idYGO;

        return $this;
    }
}
