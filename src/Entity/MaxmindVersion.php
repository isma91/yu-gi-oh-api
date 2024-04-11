<?php

namespace App\Entity;

use App\Repository\MaxmindVersionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: MaxmindVersionRepository::class)]
class MaxmindVersion
{
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $asn = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $country = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $city = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAsn(): ?\DateTimeInterface
    {
        return $this->asn;
    }

    public function setAsn(?\DateTimeInterface $asn): static
    {
        $this->asn = $asn;

        return $this;
    }

    public function getCountry(): ?\DateTimeInterface
    {
        return $this->country;
    }

    public function setCountry(?\DateTimeInterface $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getCity(): ?\DateTimeInterface
    {
        return $this->city;
    }

    public function setCity(?\DateTimeInterface $city): static
    {
        $this->city = $city;

        return $this;
    }
}
