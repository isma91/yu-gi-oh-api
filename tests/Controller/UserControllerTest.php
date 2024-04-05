<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use JsonException;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends AbstractWebTestCase
{
    /**
     * @throws JsonException
     */
    public function testBaseUser(): void
    {
        static::expectRouteNotFound("/user");
    }

    /**
     * @throws JsonException
     */
    public function testLoginUserWithoutParameter(): void
    {
        static::expectRouteFieldEmpty(
            "/user/login",
            "username",
            static::REQUEST_POST
        );
    }

    /**
     * @throws JsonException
     */
    public function testLoginUserWithoutUsername(): void
    {
        static::expectRouteFieldEmpty(
            "/user/login",
            "username",
            static::REQUEST_POST,
            ["password" => "test"]
        );
    }

    /**
     * @throws JsonException
     */
    public function testLoginUserWithoutPassword(): void
    {
        static::expectRouteFieldEmpty(
            "/user/login",
            "username",
            static::REQUEST_POST,
            ["password" => "test"]
        );
    }

    /**
     * @throws JsonException
     */
    public function testLoginUserWithBadCredential(): void
    {
        [
            "status" => $status,
        ] = static::getRequestInfo(
            "/user/login",
            static::REQUEST_POST,
            ["username" => "bad-username", "password" => "password9999"]
        );
        $this->assertSame(Response::HTTP_BAD_REQUEST, $status);
    }

    /**
     * @throws JsonException
     */
    public function testLoginUserWithGoodCredential(): void
    {
        [
            "status" => $status,
        ] = static::runProtectedRoute(
            "/user/login",
            static::REQUEST_POST,
            static::$userCredentialByRoleArray["user"]
        );
        $this->assertSame(Response::HTTP_OK, $status);
    }
}
