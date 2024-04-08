<?php

namespace App\Service\Tool\UserToken;

use App\Entity\User as UserEntity;
use App\Entity\UserToken as UserTokenEntity;
use App\Repository\UserTokenRepository;
use App\Service\Tool\Abstract\AbstractORM;
use Doctrine\ORM\EntityManagerInterface;

class ORM extends AbstractORM
{

    public function __construct(EntityManagerInterface $em, UserTokenRepository $repository)
    {
        parent::__construct($em, $repository);
    }

    /**
     * @param UserEntity $userEntity
     * @param string $token
     * @return UserTokenEntity|null
     */
    public function findByUserAndToken(UserEntity $userEntity, string $token): ?UserTokenEntity
    {
        return $this->repository->findOneBy(["user" => $userEntity, "token" => $token]);
    }
}