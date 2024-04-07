<?php

namespace App\Tests\Service;

use App\Repository\UserRepository;
use App\Service\Tool\User\Auth;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AbstractTestService extends KernelTestCase
{

    /**
     * Store it here to be quickly updated if we change in UserTestFixtures
     * @var array|array[]
     */
    public static array $userCredentialByRoleArray = [
        "user" => ["username" => "test-user", "password" => "password123"],
        "admin" => ["username" => "test-admin", "password" => "password123"],
    ];

    public static function getService(string $className): object
    {
        return static::getContainer()->get($className);
    }

    /**
     * Simulate a connection to get user's JWT
     * @param bool $isAdmin
     * @return string
     * @throws EntityNotFoundException
     */
    public static function getJWT(bool $isAdmin = FALSE): string
    {
        $userType = ($isAdmin === TRUE) ? "admin": "user";
        $userCredential = self::$userCredentialByRoleArray[$userType];
        $userRepository = self::getService(UserRepository::class);
        $user = $userRepository->findOneBy(["username" => $userCredential["username"]]);
        if ($user === NULL) {
            throw new EntityNotFoundException("User test not found, maybe you forgot to run the UserTestFixtures before testing ??");
        }
        $userAuthService = self::getService(Auth::class);
        [
            "jwt" => $jwt
        ] = $userAuthService->loginAndGetInfo($user);
        return $jwt;
    }

    /**
     * @param object $service
     * @param string $fieldName
     * @param bool $isAdmin
     * @return void
     * @throws EntityNotFoundException
     */
    public static function getAll(object $service, string $fieldName, bool $isAdmin = FALSE): void
    {
        $jwt = self::getJWT($isAdmin);
        $getAllResult = $service->getAll($jwt);
        self::assertEmpty($getAllResult["error"]);
        self::assertNotEmpty($getAllResult[$fieldName]);
    }
}