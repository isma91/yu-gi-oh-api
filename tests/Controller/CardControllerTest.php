<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\CardRepository;
use Doctrine\ORM\EntityNotFoundException;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

class CardControllerTest extends AbstractWebTestCase
{
    private string $baseUrl = "/card";

    /**
     * @throws JsonException
     */
    public function testCardBase(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testCardGetRandom(): void
    {
        [
            "content" => $content,
            "status" => $status
        ] = self::getRequestInfo($this->baseUrl . "/random", static::REQUEST_GET);
        $this->assertSame(Response::HTTP_OK, $status);
        $this->assertNotEmpty($content["data"]["card"]);
    }

    /**
     * Get Card uuid as string to be used in route
     * @return string
     * @throws EntityNotFoundException
     */
    public function getCardUuid(): string
    {
        static::initiateClient();
        $cardRepository = self::$client->getContainer()->get(CardRepository::class);
        $slugName = "cosmo-queen";
        $result = $cardRepository->findBy(["slugName" => $slugName]);
        if (empty($result) === TRUE) {
            throw new EntityNotFoundException(
                sprintf(
                    "Card with slugName '%s' not found, maybe you don't run Import before testing ??",
                    $slugName
                )
            );
        }
        return $result[0]->getUuid()->__toString();
    }

    /**
     * @throws JsonException
     * @throws EntityNotFoundException
     */
    public function testCardGetInfoWithoutAuth(): void
    {
        static::expectRouteUnauthorized(
            $this->baseUrl . "/info/" . $this->getCardUuid()
        );
    }

    /**
     * @throws JsonException
     * @throws EntityNotFoundException
     */
    public function testCardGetInfoWithAuth(): void
    {
        [
            "status" => $status,
            "content" => $content
        ] = static::runProtectedRoute(
            $this->baseUrl . "/info/" . $this->getCardUuid()
        );
        $this->assertSame(Response::HTTP_OK, $status);
        $this->assertNotEmpty($content["data"]["card"]);
    }
}
