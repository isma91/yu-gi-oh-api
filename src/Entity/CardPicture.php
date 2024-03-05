<?php

namespace App\Entity;

use App\Repository\CardPictureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: CardPictureRepository::class)]
class CardPicture
{
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
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

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): static
    {
        $this->picture = $picture;

        return $this;
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
