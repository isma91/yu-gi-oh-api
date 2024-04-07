<?php

namespace App\Tests\Service;

use App\Service\User;
use Doctrine\ORM\EntityNotFoundException;

class UserServiceTest extends AbstractTestService
{
    private User $service;

    public function setUp(): void
    {
        $this->service = self::getService(User::class);
        parent::setUp();
    }

    public function testUserLoginWithBadCredential(): void
    {
        $userCredential = self::$userCredentialByRoleArray["user"];
        $userCredential["username"] .= "testtest";
        [
            "error" => $error,
        ] = $this->service->login($userCredential);
        $this->assertNotEmpty($error);
    }

    public function testUserLoginWithGoodCredential(): void
    {
        $userCredential = self::$userCredentialByRoleArray["user"];
        [
            "error" => $error,
            "user" => $userInfo
        ] = $this->service->login($userCredential);
        $this->assertEmpty($error);
        $this->assertSame($userInfo["username"], $userCredential["username"]);
    }

    /**
     * @return void
     * @throws EntityNotFoundException
     */
    public function testUserLoginWithBadJwt(): void
    {
        $jwt = self::getJWT();
        $jwt[0] = "f";
        [
            "error" => $error,
            "user" => $userInfo
        ] = $this->service->loginFromJwt($jwt);
        $this->assertNotEmpty($error);
        $this->assertEmpty($userInfo);
    }

    /**
     * @return void
     * @throws EntityNotFoundException
     */
    public function testUserLoginWithGoodJwt(): void
    {
        $userCredential = self::$userCredentialByRoleArray["user"];
        $jwt = self::getJWT();
        [
            "error" => $error,
            "user" => $userInfo
        ] = $this->service->loginFromJwt($jwt);
        $this->assertEmpty($error);
        $this->assertSame($userInfo["username"], $userCredential["username"]);
    }

    /**
     * @return void
     * @throws EntityNotFoundException
     */
    public function testUserLogout(): void
    {
        $jwt = self::getJWT();
        [
            "error" => $error,
        ] = $this->service->logout($jwt);
        $this->assertEmpty($error);
    }
}
