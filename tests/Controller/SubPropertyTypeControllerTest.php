<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use JsonException;
use Symfony\Component\HttpFoundation\Response;

class SubPropertyTypeControllerTest extends AbstractWebTestCase
{
    private string $baseUrl = "/sub-property-type";

    /**
     * @throws JsonException
     */
    public function testSubPropertyTypeBase(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testSubPropertyTypeGetAllWithoutAuth(): void
    {
        static::expectRouteUnauthorized($this->baseUrl . "/all");
    }

    /**
     * @throws JsonException
     */
    public function testSubPropertyTypeGetAllWithAuth(): void
    {
        static::getAllProtected($this->baseUrl . "/all", "subPropertyType");
    }
}
