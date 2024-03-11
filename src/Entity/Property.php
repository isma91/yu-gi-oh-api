<?php

namespace App\Entity;

use App\Repository\PropertyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "The Level value of the Card Monster. A Card can have one Property."
)]
#[ORM\Entity(repositoryClass: PropertyRepository::class)]
class Property
{
    use TimestampableEntity;

    #[OA\Property(description: "Internal unique identifier of the Property", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["search_card", "property_type_list"])]
    private ?int $id = null;

    #[OA\Property(description: "Name of the Property", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "property_type_list"])]
    private ?string $name = null;

    #[OA\Property(description: "Slugify name of the Property", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "property_type_list"])]
    private ?string $slugName = null;

    #[ORM\ManyToOne(cascade: ["persist"])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["search_card"])]
    private ?PropertyType $propertyType = null;

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

    public function getPropertyType(): ?PropertyType
    {
        return $this->propertyType;
    }

    public function setPropertyType(?PropertyType $propertyType): static
    {
        $this->propertyType = $propertyType;

        return $this;
    }
}
