<?php

namespace App\Entity;

use App\Repository\ArchetypeRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "An Archetype is a group of cards that are supported due to their name. A Card can only have one Archetype."
)]
#[ORM\Entity(repositoryClass: ArchetypeRepository::class)]
class Archetype
{
    use TimestampableEntity;

    #[OA\Property(description: "Internal unique identifier of the Archetype", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["archetype_list", "card_info"])]
    private ?int $id = null;

    #[OA\Property(description: "Name of the Archetype", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["archetype_list", "card_info"])]
    private ?string $name = null;

    #[OA\Property(description: "Slugify name of the Archetype", type: "string", maxLength: 255, nullable: false)]
    #[ORM\Column(length: 255)]
    #[Groups(["archetype_list", "card_info"])]
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
