<?php

namespace App\Entity;

use App\Repository\DatabaseYGORepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: DatabaseYGORepository::class)]
class DatabaseYGO
{
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $databaseVersion = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $lastUpdate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDatabaseVersion(): ?float
    {
        return $this->databaseVersion;
    }

    public function setDatabaseVersion(float $databaseVersion): static
    {
        $this->databaseVersion = $databaseVersion;

        return $this;
    }

    public function getLastUpdate(): ?\DateTimeInterface
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(\DateTimeInterface $lastUpdate): static
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }
}
