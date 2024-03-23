<?php

namespace App\Service\Tool\Set;

use App\Entity\Set;
use App\Repository\SetRepository;
use App\Service\Tool\Abstract\AbstractORM;
use App\Service\Tool\ORMSearch;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ORM extends AbstractORM
{
    protected ORMSearch $ORMSearch;

    public function __construct(EntityManagerInterface $em, SetRepository $repository, SluggerInterface $slugger)
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
     * @return Set|null
     */
    public function findByUuid(string $uuid): ?Set
    {
        return $this->repository->findOneBy(["uuid" => $uuid]);
    }
}