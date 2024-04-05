<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use JsonException;
use Symfony\Component\HttpFoundation\Response;

class SetControllerTest extends AbstractWebTestCase
{
    private string $baseUrl = "/set";

    /**
     * @throws JsonException
     */
    public function testSetBase(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testSetGetInfoWithIdNotNumber(): void
    {
        [
            "status" => $status
        ] = static::runProtectedRoute($this->baseUrl . "/info/azerty");
        $this->assertSame(Response::HTTP_NOT_FOUND, $status);
    }

    /**
     * @throws JsonException
     */
    public function testSetGetInfoWithIdNegativeNumber(): void
    {
        [
            "status" => $status
        ] = static::runProtectedRoute($this->baseUrl . "/info/-1234");
        $this->assertSame(Response::HTTP_NOT_FOUND, $status);
    }

    /**
     * @throws JsonException
     */
    public function testSetGetInfoWithBadId(): void
    {
        [
            "status" => $status,
            "content" => $content
        ] = static::runProtectedRoute($this->baseUrl . "/info/0");
        $this->assertSame(Response::HTTP_BAD_REQUEST, $status);
        $this->assertEmpty($content["data"]["set"]);
    }

    /**
     * @throws JsonException
     */
    public function testSetGetInfoWithGoodId(): void
    {
        static::getAllProtected($this->baseUrl . "/info/1", "set");
    }
}
