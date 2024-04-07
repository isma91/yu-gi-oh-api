<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use JsonException;
use Symfony\Component\HttpFoundation\Response;

class PropertyTypeControllerTest extends AbstractWebTestCase
{
    private string $baseUrl = "/property-type";

    /**
     * @throws JsonException
     */
    public function testPropertyTypeBase(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testPropertyTypeGetAllWithoutAuth(): void
    {
        static::expectRouteUnauthorized($this->baseUrl . "/all");
    }

    /**
     * @throws JsonException
     */
    public function testPropertyTypeGetAllWithAuth(): void
    {
        static::getAllProtected($this->baseUrl . "/all", "propertyType");
    }
}
