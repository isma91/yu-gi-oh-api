<?php

namespace App\Entity;

use App\Repository\SubCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "The Sub Category of Card. A Card can have one SubCategory."
)]
#[ORM\Entity(repositoryClass: SubCategoryRepository::class)]
class SubCategory
{
    use TimestampableEntity;

    #[OA\Property(description: "Internal unique identifier of the SubCategory", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["search_card", "category_list", "card_info"])]
    private ?int $id = null;

    #[OA\Property(description: "Name of the SubCategory", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "category_list", "card_info"])]
    private ?string $name = null;

    #[OA\Property(description: "Slugify name of the SubCategory", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "category_list", "card_info"])]
    private ?string $slugName = null;

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
}
