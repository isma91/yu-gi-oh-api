<?php

namespace App\DataFixtures;

use App\Entity\User as UserEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserTestFixtures extends Fixture implements FixtureGroupInterface
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $current = new \DateTime();
        $usernameTestAdmin = "test-admin";
        $usernameTestUser = "test-user";
        $password = "password123";
        $userTestAdmin = new UserEntity();
        $userTestAdmin->setUsername($usernameTestAdmin)
            ->setPassword(
                $this->userPasswordHasher->hashPassword($userTestAdmin, $password)
            )
            ->addAdminRole()
            ->setCreatedAt($current)
            ->setUpdatedAt($current);
        $manager->persist($userTestAdmin);
        $userTestUser = new UserEntity();
        $userTestUser->setUsername($usernameTestUser)
            ->setPassword(
                $this->userPasswordHasher->hashPassword($userTestUser, $password)
            )
            ->setCreatedAt($current)
            ->setUpdatedAt($current);
        $manager->persist($userTestUser);
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ["user-test"];
    }
}
