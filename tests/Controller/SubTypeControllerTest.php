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
    public function testBaseSubType(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testGetAllSubTypeWithoutAuth(): void
    {
        static::expectRouteUnauthorized($this->baseUrl . "/all");
    }

    /**
     * @throws JsonException
     */
    public function testGetAllSubTypeWithAuth(): void
    {
        static::getAllProtected($this->baseUrl . "/all", "subType");
    }
}
