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
    public function testCardAttributeBase(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testCardAttributeGetAllWithoutAuth(): void
    {
        static::expectRouteUnauthorized($this->baseUrl . "/all");
    }

    /**
     * @throws JsonException
     */
    public function testCardAttributeGetAllWithAuth(): void
    {
        static::getAllProtected($this->baseUrl . "/all", "cardAttribute");
    }
}
