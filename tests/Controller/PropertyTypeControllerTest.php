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
    public function testBasePropertyType(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testGetAllPropertyTypeWithoutAuth(): void
    {
        static::expectRouteUnauthorized($this->baseUrl . "/all");
    }

    /**
     * @throws JsonException
     */
    public function testGetAllPropertyTypeWithAuth(): void
    {
        static::getAllProtected($this->baseUrl . "/all", "propertyType");
    }
}
