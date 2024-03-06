<?php

namespace App\Service\Tool\User;


use App\Entity\User as UserEntity;
use App\Repository\UserRepository;
use App\Service\Tool\AbstractORMSlugName;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ORM extends AbstractORMSlugName
{

    public function __construct(EntityManagerInterface $em, UserRepository $repository, SluggerInterface $slugger)
    {
        parent::__construct($em, $repository, $slugger);
    }

    /**
     * @param string $username
     * @return UserEntity|null
     */
    public function findByUserIdentifiant(string $username): ?UserEntity
    {
        return $this->repository->findOneBy(["username" => $username]);
    }
}