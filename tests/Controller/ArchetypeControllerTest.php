<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use JsonException;
use Symfony\Component\HttpFoundation\Response;

class ArchetypeControllerTest extends AbstractWebTestCase
{
    private string $baseUrl = "/archetype";

    /**
     * @throws JsonException
     */
    public function testBaseArchetype(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testGetAllArchetypeWithoutAuth(): void
    {
        static::expectRouteUnauthorized($this->baseUrl . "/all");
    }

    /**
     * @throws JsonException
     */
    public function testGetAllArchetypeWithAuth(): void
    {
        static::getAllProtected($this->baseUrl . "/all", "archetype");
    }
}
