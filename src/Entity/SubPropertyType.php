<?php

namespace App\Entity;

use App\Repository\SubPropertyTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "The Pendulum/Link specific requirement of a Card Monster. A Card can have one or not a SubPropertyType."
)]
#[ORM\Entity(repositoryClass: SubPropertyTypeRepository::class)]
class SubPropertyType
{
    use TimestampableEntity;

    #[OA\Property(description: "Internal unique identifier of the SubPropertyType", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["search_card", "sub_property_type_list"])]
    private ?int $id = null;

    #[OA\Property(description: "Name of the SubPropertyType", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "sub_property_type_list"])]
    private ?string $name = null;

    #[OA\Property(description: "Slugify name of the SubPropertyType", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "sub_property_type_list"])]
    private ?string $slugName = null;

    #[OA\Property(description: "Array of SubProperty children")]
    #[ORM\OneToMany(mappedBy: 'subPropertyType', targetEntity: SubProperty::class)]
    #[Groups(["sub_property_type_list"])]
    private Collection $subProperties;

    public function __construct()
    {
        $this->subProperties = new ArrayCollection();
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
     * @return Collection<int, SubProperty>
     */
    public function getSubProperties(): Collection
    {
        return $this->subProperties;
    }

    public function addSubProperty(SubProperty $subProperty): static
    {
        if (!$this->subProperties->contains($subProperty)) {
            $this->subProperties->add($subProperty);
            $subProperty->setSubPropertyType($this);
        }

        return $this;
    }

    public function removeSubProperty(SubProperty $subProperty): static
    {
        if ($this->subProperties->removeElement($subProperty)) {
            // set the owning side to null (unless already changed)
            if ($subProperty->getSubPropertyType() === $this) {
                $subProperty->setSubPropertyType(null);
            }
        }

        return $this;
    }
}
