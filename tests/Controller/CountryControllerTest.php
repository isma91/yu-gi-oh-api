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
    public function testBaseCountry(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testGetAllCountryWithoutAuth(): void
    {
        static::expectRouteUnauthorized($this->baseUrl . "/all");
    }

    /**
     * @throws JsonException
     */
    public function testGetAllCountryWithAuth(): void
    {
        static::getAllProtected($this->baseUrl . "/all", "country");
    }
}
