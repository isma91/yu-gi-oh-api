<?php

namespace App\Service\Tool;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

class ORMSlugName
{
    public ServiceEntityRepository $repository;
    public SluggerInterface $slugger;

    public function __construct(ServiceEntityRepository $repository, SluggerInterface $slugger)
    {
        $this->repository = $repository;
        $this->slugger = $slugger;
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
        return $this->repository->findOneBy(["slugName" => $slugName]);
    }

}