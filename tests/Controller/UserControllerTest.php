<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use JsonException;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends AbstractWebTestCase
{
    public string $baseUrl = "/user";
    /**
     * @throws JsonException
     */
    public function testUserBase(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testUserLoginWithoutParameter(): void
    {
        static::expectRouteFieldEmpty(
            $this->baseUrl . "/login",
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
            $this->baseUrl . "/login",
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
            $this->baseUrl . "/login",
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
            $this->baseUrl . "/login",
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
            $this->baseUrl . "/login",
            static::REQUEST_POST,
            static::$userCredentialByRoleArray["user"]
        );
        $this->assertSame(Response::HTTP_OK, $status);
    }

    /**
     * @throws JsonException
     */
    public function testUserRefreshLoginWithBadJwt(): void
    {
        $jwt = static::generateJWTFromUserType();
        $jwt[0] = "f";
        [
            "status" => $status,
            "content" => $content
        ] = static::getRequestInfo(
            $this->baseUrl . "/refresh-login",
            static::REQUEST_GET,
            NULL,
            static::createHeaderArrayForJwt($jwt)
        );
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $status);
    }

    /**
     * @throws JsonException
     */
    public function testUserRefreshLoginWithGoodJwt(): void
    {
        [
            "status" => $status
        ] = static::runProtectedRoute($this->baseUrl . "/refresh-login");
        $this->assertSame(Response::HTTP_OK, $status);
    }

    /**
     * @throws JsonException
     */
    public function testUserLogout(): void
    {
        [
            "status" => $status
        ] = static::runProtectedRoute($this->baseUrl . "/logout");
        $this->assertSame(Response::HTTP_OK, $status);
    }
}
