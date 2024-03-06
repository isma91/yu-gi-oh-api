<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use App\Entity\User as UserEntity;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class User extends Fixture implements FixtureGroupInterface
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $current = new \DateTime();
        $username = "@ChangeMe@";
        $password = "@ChangeMe@";
        $token = md5(uniqid($username, TRUE));
        $user = new UserEntity();
        $user->setUsername($username)
            ->setPassword(
                $this->userPasswordHasher->hashPassword($user, $password)
            )
            ->addAdminRole()
            ->setToken($token)
            ->setCreatedAt($current)
            ->setUpdatedAt($current);
        $manager->persist($user);
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ["user"];
    }
}
