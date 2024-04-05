<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use JsonException;
use Symfony\Component\HttpFoundation\Response;

class CountryControllerTest extends AbstractWebTestCase
{
    private string $baseUrl = "/country";

    /**
     * @throws JsonException
     */
    public function testCountryBase(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testCountryGetAllWithoutAuth(): void
    {
        static::expectRouteUnauthorized($this->baseUrl . "/all");
    }

    /**
     * @throws JsonException
     */
    public function testCountryGetAllWithAuth(): void
    {
        static::getAllProtected($this->baseUrl . "/all", "country");
    }
}
