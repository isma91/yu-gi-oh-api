<?php

namespace App\Entity;

use App\Repository\CardAttributeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CardAttributeRepository::class)]
class CardAttribute
{
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["search_card", "card_attribute_list"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "card_attribute_list"])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(["search_card", "card_attribute_list"])]
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
