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
    public function testArchetypeBase(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testArchetypeGetAllWithoutAuth(): void
    {
        static::expectRouteUnauthorized($this->baseUrl . "/all");
    }

    /**
     * @throws JsonException
     */
    public function testArchetypeGetAllWithAuth(): void
    {
        static::getAllProtected($this->baseUrl . "/all", "archetype");
    }
}
