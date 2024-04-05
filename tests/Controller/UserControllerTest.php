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
    public function testUserBase(): void
    {
        static::expectRouteNotFound("/user");
    }

    /**
     * @throws JsonException
     */
    public function testUserLoginWithoutParameter(): void
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
    public function testUserLoginWithoutUsername(): void
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
    public function testUserLoginWithoutPassword(): void
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
    public function testUserLoginWithBadCredential(): void
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
    public function testUserLoginWithGoodCredential(): void
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
