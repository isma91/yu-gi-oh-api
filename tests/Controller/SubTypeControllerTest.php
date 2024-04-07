<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use JsonException;
use Symfony\Component\HttpFoundation\Response;

class SubTypeControllerTest extends AbstractWebTestCase
{
    private string $baseUrl = "/sub-type";

    /**
     * @throws JsonException
     */
    public function testSubTypeBase(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testSubTypeGetAllWithoutAuth(): void
    {
        static::expectRouteUnauthorized($this->baseUrl . "/all");
    }

    /**
     * @throws JsonException
     */
    public function testSubTypeGetAllWithAuth(): void
    {
        static::getAllProtected($this->baseUrl . "/all", "subType");
    }
}
