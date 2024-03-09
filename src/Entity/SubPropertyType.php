<?php

namespace App\Entity;

use App\Repository\SubPropertyTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SubPropertyTypeRepository::class)]
class SubPropertyType
{
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups("search_card")]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups("search_card")]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups("search_card")]
    private ?string $slugName = null;

    #[ORM\OneToMany(mappedBy: 'subPropertyType', targetEntity: SubProperty::class)]
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
