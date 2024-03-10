<?php

namespace App\Service\Tool\User;


use App\Entity\User as UserEntity;
use App\Repository\UserRepository;
use App\Service\Tool\Abstract\AbstractORM;
use App\Service\Tool\ORMSlugName;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ORM extends AbstractORM
{
    protected ORMSlugName $ORMSlugName;

    public function __construct(EntityManagerInterface $em, UserRepository $repository, SluggerInterface $slugger)
    {
        $this->ORMSlugName = new ORMSlugName($repository, $slugger);
        parent::__construct($em, $repository);
    }

    /**
     * @return ORMSlugName
     */
    public function getORMSlugName(): ORMSlugName
    {
        return $this->ORMSlugName;
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