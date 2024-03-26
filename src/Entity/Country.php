<?php

namespace App\Entity;

use App\Repository\CountryRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: CountryRepository::class)]
class Country
{
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $slugName = null;

    #[ORM\Column(length: 2)]
    private ?string $alpha2 = null;

    #[ORM\Column(length: 3)]
    private ?string $alpha3 = null;

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

    public function getAlpha2(): ?string
    {
        return $this->alpha2;
    }

    public function setAlpha2(string $alpha2): static
    {
        $this->alpha2 = $alpha2;

        return $this;
    }

    public function getAlpha3(): ?string
    {
        return $this->alpha3;
    }

    public function setAlpha3(string $alpha3): static
    {
        $this->alpha3 = $alpha3;

        return $this;
    }
}
