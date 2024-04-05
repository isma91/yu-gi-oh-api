<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use JsonException;
use Symfony\Component\HttpFoundation\Response;

class TypeControllerTest extends AbstractWebTestCase
{
    private string $baseUrl = "/type";

    /**
     * @throws JsonException
     */
    public function testBaseType(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testGetAllTypeWithoutAuth(): void
    {
        static::expectRouteUnauthorized($this->baseUrl . "/all");
    }

    /**
     * @throws JsonException
     */
    public function testGetAllTypeWithAuth(): void
    {
        static::getAllProtected($this->baseUrl . "/all", "type");
    }
}
