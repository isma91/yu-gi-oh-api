<?php

namespace App\Service\Tool\Card;


use App\Entity\Card;
use App\Repository\CardRepository;
use App\Service\Tool\Abstract\AbstractORM;
use App\Service\Tool\ORMSearch;
use Doctrine\ORM\EntityManagerInterface;

class ORM extends AbstractORM
{
    protected ORMSearch $ORMSearch;

    public function __construct(EntityManagerInterface $em, CardRepository $repository)
    {
        $this->ORMSearch = new ORMSearch($repository);
        parent::__construct($em, $repository);
    }

    /**
     * @return ORMSearch
     */
    public function getORMSearch(): ORMSearch
    {
        return $this->ORMSearch;
    }

    /**
     * @param string $uuid
     * @return Card|null
     */
    public function findByUuid(string $uuid): ?Card
    {
        return $this->repository->findOneBy(["uuid" => $uuid]);
    }

    /**
     * @return Card|null
     */
    public function findRandom(): ?Card
    {
        return $this->repository->findRandom();
    }
}