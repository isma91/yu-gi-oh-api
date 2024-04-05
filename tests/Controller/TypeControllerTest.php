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
    public function testTypeBase(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testTypeGetAllWithoutAuth(): void
    {
        static::expectRouteUnauthorized($this->baseUrl . "/all");
    }

    /**
     * @throws JsonException
     */
    public function testTypeGetAllWithAuth(): void
    {
        static::getAllProtected($this->baseUrl . "/all", "type");
    }
}
