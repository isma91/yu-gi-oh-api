<?php

namespace App\Entity;

use App\Repository\CountryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "The Country Entity, name with alpha code."
)]
#[ORM\Entity(repositoryClass: CountryRepository::class)]
class Country
{
    use TimestampableEntity;
    #[OA\Property(description: "Internal unique identifier of the Country", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["country_list"])]
    private ?int $id = null;

    #[OA\Property(description: "Name of the Country", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["country_list"])]
    private ?string $name = null;

    #[OA\Property(description: "Slugify name of the Country", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["country_list"])]
    private ?string $slugName = null;

    #[OA\Property(description: "ISO 3166-1 alpha2 code the Country", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 2)]
    #[Groups(["country_list"])]
    private ?string $alpha2 = null;

    #[OA\Property(description: "ISO 3166-1 alpha3 code the Country", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 3)]
    #[Groups(["country_list"])]
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
