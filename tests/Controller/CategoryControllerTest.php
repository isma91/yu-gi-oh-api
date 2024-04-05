<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use JsonException;
use Symfony\Component\HttpFoundation\Response;

class CategoryControllerTest extends AbstractWebTestCase
{
    private string $baseUrl = "/category";

    /**
     * @throws JsonException
     */
    public function testBaseCategory(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testGetAllCategoryWithoutAuth(): void
    {
        static::expectRouteUnauthorized($this->baseUrl . "/all");
    }

    /**
     * @throws JsonException
     */
    public function testGetAllCategoryWithAuth(): void
    {
        static::getAllProtected($this->baseUrl . "/all", "category");
    }
}
