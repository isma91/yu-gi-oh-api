<?php

namespace App\Service\Tool;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Abstract class to avoid the use of repository too ofter
 * More specific than an Interface
 */
abstract class AbstractORM
{
    protected ServiceEntityRepository $repository;

    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, ServiceEntityRepository $repository)
    {
        $this->em = $em;
        $this->repository = $repository;
    }

    /**
     * @param int $id
     * @return object|null
     */
    public function findById(int $id): ?object
    {
        return $this->repository->find($id);
    }

    /**
     * @param array $filters
     * @return object|null
     */
    public function findOneBy(array $filters): ?object
    {
        return $this->repository->findOneBy($filters);
    }

    /**
     * @return object[]
     */
    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function persist(object $entity): void
    {
        $this->em->persist($entity);
    }

    public function flush(): void
    {
        $this->em->flush();
    }
}