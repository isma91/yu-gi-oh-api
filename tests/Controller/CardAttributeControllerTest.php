<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use JsonException;
use Symfony\Component\HttpFoundation\Response;

class CardAttributeControllerTest extends AbstractWebTestCase
{
    private string $baseUrl = "/card-attribute";

    /**
     * @throws JsonException
     */
    public function testBaseCardAttribute(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testGetAllCardAttributeWithoutAuth(): void
    {
        static::expectRouteUnauthorized($this->baseUrl . "/all");
    }

    /**
     * @throws JsonException
     */
    public function testGetAllCardAttributeWithAuth(): void
    {
        static::getAllProtected($this->baseUrl . "/all", "cardAttribute");
    }
}
