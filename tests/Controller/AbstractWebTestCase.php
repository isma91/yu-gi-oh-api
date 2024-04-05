<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use InvalidArgumentException;
use JsonException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractWebTestCase extends WebTestCase
{
    public const REQUEST_GET = "GET";
    public const REQUEST_POST = "POST";
    public const REQUEST_PUT = "PUT";
    public const REQUEST_DELETE = "DELETE";

    /**
     * Use class property to avoid the Logic Exception because we can't boot the Kernel more than one
     * @var KernelBrowser|null
     */
    public static ?KernelBrowser $client = NULL;

    /**
     * Store it here to be quickly updated if we change in UserTestFixtures
     * @var array|array[]
     */
    public static array $userCredentialByRoleArray = [
        "user" => ["username" => "test-user", "password" => "password123"],
        "admin" => ["username" => "test-admin", "password" => "password123"],
    ];

    /**
     * Create the header array to add the JWT when we send the request
     * @param string $jwt
     * @return string[]
     */
    public static function createHeaderArrayForJwt(string $jwt): array
    {
        return ["HTTP_AUTHORIZATION" => "Bearer " . $jwt];
    }

    /**
     * Create a client if not exist, send request with data & header if any.
     * Return a JsonResponse or a basic string
     * @param string $url
     * @param string $method
     * @param array|null $data
     * @param array|null $headers
     * @return array[
     * "status" => int,
     * "content" => string|array[
     *      "error" => string,
     *      "errorDebug" => string|undefined,
     *      "data" => mixed|null,
     *      ]
     * ]
     * @throws JsonException
     */
    public static function getRequestInfo(
        string $url,
        string $method,
        ?array $data = NULL,
        ?array $headers = NULL
    ): array
    {
        $method = strtoupper($method);
        $acceptedMethodArray = [
            self::REQUEST_GET,
            self::REQUEST_POST,
            self::REQUEST_DELETE,
            self::REQUEST_PUT,
            self::REQUEST_PUT
        ];
        if (in_array($method, $acceptedMethodArray, TRUE) === FALSE) {
            throw new InvalidArgumentException(sprintf("method %s not valid", $method));
        }
        if (self::$client === NULL) {
            self::$client = static::createClient();
        }
        if ($data === NULL) {
            $data = [];
        }
        if ($headers === NULL) {
            $headers = [];
        }
        self::$client->request($method, $url, $data, [], $headers);
        $response = self::$client->getResponse();
        $requestContent = $response->getContent();
        if (str_starts_with($requestContent, "{") === TRUE && str_ends_with($requestContent, "}") === TRUE) {
            $content = json_decode($requestContent, TRUE, 512, JSON_THROW_ON_ERROR);
        } else {
            $content = $requestContent;
        }
        $status = $response->getStatusCode();
        return ["content" => $content, "status" => $status];
    }

    /**
     * Run a non-existant route
     * @param string $url
     * @param string $method
     * @param array|null $data
     * @param array|null $headers
     * @return void
     * @throws JsonException
     */
    public static function expectRouteNotFound(
        string $url,
        string $method = self::REQUEST_GET,
        ?array $data = NULL,
        ?array $headers = NULL
    ): void
    {
        [
            "content" => $content,
            "status" => $status
        ] = self::getRequestInfo($url, $method, $data, $headers);
        self::assertSame(Response::HTTP_NOT_FOUND, $status);
    }

    /**
     * Run a protected route without Authorization header
     * @param string $url
     * @param string $method
     * @param array|null $data
     * @param array|null $headers
     * @return void
     * @throws JsonException
     */
    public static function expectRouteUnauthorized(
        string $url,
        string $method = self::REQUEST_GET,
        ?array $data = NULL,
        ?array $headers = NULL
    ): void
    {
        [
            "content" => $content,
            "status" => $status
        ] = self::getRequestInfo($url, $method, $data, $headers);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $status);
    }

    /**
     * Run a route with some missing field
     * @param string $url
     * @param string $fieldName
     * @param string $method
     * @param array|null $data
     * @param array|null $headers
     * @return void
     * @throws JsonException
     */
    public static function expectRouteFieldEmpty(
        string $url,
        string $fieldName,
        string $method = self::REQUEST_GET,
        ?array $data = NULL,
        ?array $headers = NULL
    ): void
    {
        [
            "content" => $content,
            "status" => $status
        ] = self::getRequestInfo($url, $method, $data, $headers);
        $contentErrorText = sprintf("Field '%s' is missing", $fieldName);
        self::assertSame(Response::HTTP_BAD_REQUEST, $status);
        self::assertSame($contentErrorText, $content['error']);
    }

    /**
     * Generate a JWT from a login because we have custom firewall with JWT implementation
     * @param bool $isAdmin
     * @return string
     * @throws JsonException
     */
    public static function generateJWTFromUserType(bool $isAdmin = FALSE): string
    {
        $userType = ($isAdmin === TRUE) ? "admin": "user";
        $userCredential = self::$userCredentialByRoleArray[$userType];
        [
            "status" => $status,
            "content" => $content
        ] = static::getRequestInfo(
            "/user/login",
            static::REQUEST_POST,
            $userCredential
        );
        self::assertSame(Response::HTTP_OK, $status);
        return $content["data"]["userInfo"]["jwt"];
    }

    /**
     * Connect a User and run to a specific route with the JWT
     * @param string $url
     * @param string $method
     * @param array|null $data
     * @param bool $isAdmin
     * @return array
     * @throws JsonException
     */
    public static function runProtectedRoute(
        string $url,
        string $method = self::REQUEST_GET,
        ?array $data = NULL,
        bool $isAdmin = FALSE
    ): array
    {
        $jwt = static::generateJWTFromUserType($isAdmin);
        return static::getRequestInfo($url, $method, $data, static::createHeaderArrayForJwt($jwt));
    }

    /**
     * Get all an entity from a specific route
     * We check if we get at least one element of the data part
     * @param string $url
     * @param string $fieldName
     * @param bool $isAdmin
     * @return void
     * @throws JsonException
     */
    public static function getAllProtected(
        string $url,
        string $fieldName,
        bool $isAdmin = FALSE
    ): void
    {
        $jwt = static::generateJWTFromUserType($isAdmin);
        [
            "status" => $status,
            "content" => $content
        ] = static::getRequestInfo($url, self::REQUEST_GET, NULL, static::createHeaderArrayForJwt($jwt));
        self::assertSame(Response::HTTP_OK, $status);
        self::assertNotEmpty($content["data"][$fieldName]);
    }
}