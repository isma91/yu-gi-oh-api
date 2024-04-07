<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\CardRepository;
use App\Repository\SetRepository;
use Doctrine\ORM\EntityNotFoundException;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

class SearchControllerTest extends AbstractWebTestCase
{
    private string $baseUrl = "/search";

    /**
     * @throws JsonException
     */
    public function testSearchBase(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testSearchCardWithoutAuth(): void
    {
        static::expectRouteUnauthorized($this->baseUrl . "/card", self::REQUEST_POST);
    }

    /**
     * @throws JsonException
     * @throws EntityNotFoundException
     */
    public function testSearchCardWithAuth(): void
    {
        self::initiateClient();
        $cardRepository = self::$client->getContainer()->get(CardRepository::class);
        $cosmoQueen = $cardRepository->findOneBy(["slugName" => "cosmo-queen"]);
        //check that all these card exist
        if ($cosmoQueen === NULL) {
            throw new EntityNotFoundException(
                "Card 'Cosmo Queen' not found, maybe you don't run the Import before testing ??"
            );
        }
        $cardCategory = $cosmoQueen->getCategory()->getId();
        $cardAttribute = $cosmoQueen->getAttribute()->getId();
        $cardType = $cosmoQueen->getType()->getId();
        $cardLevel = $cosmoQueen->getProperty();
        $cardLevelType = $cardLevel->getPropertyType()->getId();
        $propertyRequest = (int)$cardLevel->getName() . "," . (int)$cardLevel->getName();
        $searchCardRequest = [
            "offset" => 0,
            "limit" => 1,
            "name" => "cosmo",
            "category" => $cardCategory,
            "cardAttribute" => $cardAttribute,
            "type" => $cardType,
            "propertyType" => $cardLevelType,
            "property" => $propertyRequest
        ];
        [
            "status" => $status,
            "content" => $content
        ] = static::runProtectedRoute($this->baseUrl . "/card", self::REQUEST_POST, $searchCardRequest);
        $this->assertSame(Response::HTTP_OK, $status);
        $this->assertNotEmpty($content["data"]["card"]);
        $this->assertSame($content["data"]["card"][0]["id"], $cosmoQueen->getId());
    }

    /**
     * @return void
     * @throws JsonException
     */
    public function testSearchSetWithoutAuth(): void
    {
        self::expectRouteUnauthorized($this->baseUrl . "/set", self::REQUEST_POST);
    }

    /**
     * @return void
     * @throws JsonException
     * @throws EntityNotFoundException
     */
    public function testSearchSetWithAuth(): void
    {
        self::initiateClient();
        $setRepository = self::$client->getContainer()->get(SetRepository::class);
        $sets = $setRepository->findBy(["code" => "RA01"]);
        if (empty($sets) === TRUE) {
            throw new EntityNotFoundException("Set with coe 'RA01' not found, maybe you forgot to run the Import before testing ??");
        }
        $searchSetRequest = [
            "offset" => 0,
            "limit" => 1,
            "code" => $sets[0]->getCode()
        ];
        [
            "status" => $status,
            "content" => $content
        ] = self::runProtectedRoute($this->baseUrl . "/set", self::REQUEST_POST, $searchSetRequest);
        $this->assertSame(Response::HTTP_OK, $status);
        $this->assertNotEmpty($content["data"]["set"]);
        $this->assertSame($content["data"]["set"][0]["id"], $sets[0]->getId());
    }
}
