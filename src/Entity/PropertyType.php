<?php

namespace App\Entity;

use App\Repository\PropertyTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PropertyTypeRepository::class)]
class PropertyType
{
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["search_card", "property_type_list"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "property_type_list"])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "property_type_list"])]
    private ?string $slugName = null;

    #[ORM\OneToMany(mappedBy: 'propertyType', targetEntity: Property::class)]
    #[Groups(["property_type_list"])]
    private Collection $properties;

    public function __construct()
    {
        $this->properties = new ArrayCollection();
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

    /**
     * @return Collection<int, Property>
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    public function addProperty(Property $property): static
    {
        if (!$this->properties->contains($property)) {
            $this->properties->add($property);
            $property->setPropertyType($this);
        }

        return $this;
    }

    public function removeProperty(Property $property): static
    {
        if ($this->properties->removeElement($property)) {
            // set the owning side to null (unless already changed)
            if ($property->getPropertyType() === $this) {
                $property->setPropertyType(null);
            }
        }

        return $this;
    }
}
