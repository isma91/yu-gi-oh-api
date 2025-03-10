<?php

namespace App\Service\Tool;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;


class ORMSearch
{
    protected ServiceEntityRepository $repository;

    public int $offset = 0;

    public int $limit = 15;

    public int $maxLimit = 100;

    public function __construct(ServiceEntityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * To avoid server issue we limit the max number of result display
     * @return void
     */
    public function setLimitBeforeSearch(): void
    {
        $limitToSet = ($this->limit <= $this->maxLimit) ? $this->limit : $this->maxLimit;
        $this->limit = $limitToSet;
    }

    /**
     * @param array $filter
     * @return object[]
     */
    public function findFromSearchFilter(array $filter): array
    {
        $this->setLimitBeforeSearch();
        return $this->repository->findFromSearchFilter($filter, $this->offset, $this->limit);
    }

    /**
     * @param array $filter
     * @return int
     */
    public function countFromSearchFilter(array $filter): int
    {
        return $this->repository->countFromSearchFilter($filter);
    }
}