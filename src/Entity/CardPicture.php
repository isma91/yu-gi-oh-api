<?php

namespace App\Entity;

use App\Repository\CardPictureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CardPictureRepository::class)]
class CardPicture
{
    private const CARD_PICTURE_ROUTE_BASE = "card-picture";
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups("search_card")]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $picture = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pictureSmall = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $artwork = null;

    #[ORM\ManyToOne(inversedBy: 'pictures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Card $card = null;

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

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): static
    {
        $this->picture = $picture;

        return $this;
    }

    #[Groups("search_card")]
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
