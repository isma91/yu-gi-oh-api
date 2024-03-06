<?php

namespace App\Service\Tool;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

abstract class AbstractORMSlugName extends AbstractORM
{
    public ServiceEntityRepository $repository;
    public SluggerInterface $slugger;

    public function __construct(EntityManagerInterface $em, ServiceEntityRepository $repository, SluggerInterface $slugger)
    {
        $this->repository = $repository;
        $this->slugger = $slugger;
        parent::__construct($em, $repository);
    }

    /**
     * @param string $string
     * @return string
     */
    public function slugify(string $string): string
    {
        return $this->slugger->slug($string)->lower()->toString();
    }

    /**
     * @param string $string
     * @return object|null
     */
    public function findBySlugName(string $string): ?object
    {
        $slugName = $this->slugify($string);
        return $this->findOneBy(["slugName" => $slugName]);
    }

}