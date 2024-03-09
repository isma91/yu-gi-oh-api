<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["search_card", "category_list"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "category_list"])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "category_list"])]
    private ?string $slugName = null;

    #[ORM\ManyToMany(targetEntity: SubCategory::class)]
    #[Groups(["category_list"])]
    private Collection $subCategories;

    public function __construct()
    {
        $this->subCategories = new ArrayCollection();
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
     * @return Collection<int, SubCategory>
     */
    public function getSubCategories(): Collection
    {
        return $this->subCategories;
    }

    public function addSubCategory(SubCategory $subCategory): static
    {
        if (!$this->subCategories->contains($subCategory)) {
            $this->subCategories->add($subCategory);
        }

        return $this;
    }

    public function removeSubCategory(SubCategory $subCategory): static
    {
        $this->subCategories->removeElement($subCategory);

        return $this;
    }
}
