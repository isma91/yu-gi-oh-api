<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\CardCollection;
use App\Repository\CardCollectionRepository;
use App\Repository\CardRepository;
use App\Repository\CountryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityNotFoundException;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

class CardCollectionControllerTest extends AbstractWebTestCase
{
    private string $baseUrl = "/card-collection";

    /**
     * @throws JsonException
     */
    public function testCardCollectionBase(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * @throws JsonException
     */
    public function testCardCollectionCreteWithoutAuth(): void
    {
        static::expectRouteUnauthorized($this->baseUrl . "/create", self::REQUEST_POST);
    }

    /**
     * @return array
     * @throws EntityNotFoundException
     */
    public function getCardCollectionCreateRequest(): array
    {
        self::initiateClient();
        $cardRepository = self::$client->getContainer()->get(CardRepository::class);
        $countryRepository = self::$client->getContainer()->get(CountryRepository::class);
        $cards = $cardRepository->findBy([], NULL, 3);
        $countries = $countryRepository->findBy(["slugName" => "japan"], NULL, 1);
        $country = $countries[0];
        if (empty($cards) === TRUE) {
            throw new EntityNotFoundException("No Card found, did you forget to run the Import before testing ??");
        }
        if (empty($countries) === TRUE) {
            throw new EntityNotFoundException("Country not found, did you forget to load all fixtures before testing ??");
        }
        $cardCollectionData = [];
        foreach ($cards as $card) {
            $cardId = $card->getId();
            $pictureId = 0;
            $setId = 0;
            $rarityId = 0;
            $cardPictures = $card->getPictures();
            $countryId = $country->getId();
            $cardSets = $card->getCardSets();
            if ($cardSets->count() > 0) {
                $sets = $cardSets[0]->getSets();
                $rarities = $cardSets[0]->getRarities();
                if ($sets->count() > 0) {
                    $setId = $sets[0]->getId();
                }
                if ($rarities->count() > 0) {
                    $rarityId = $rarities[0]->getId();
                }
            }
            if ($cardPictures->count() > 0) {
                $pictureId = $cardPictures[0]->getId();
            }
            $cardCollectionData[] = [
                "card" => $cardId,
                "nbCopie" => 4,
                "country" => $countryId,
                "rarity" => $rarityId,
                "set" => $setId,
                "picture" => $pictureId
            ];
        }
        return [
            "name" => "collection-test",
            "isPublic" => "true",
            "card-collection" => $cardCollectionData
        ];
    }

    /**
     * @group card-collection-get-info
     * @group card-collection-update-public
     * @group card-collection-update-info
     * @group card-collection-delete
     * @throws JsonException
     * @throws EntityNotFoundException
     */
    public function testCardCollectionCreateWithAuth(): void
    {
        $cardCollectionCreateRequest = $this->getCardCollectionCreateRequest();
        [
            "status" => $status
        ] = self::runProtectedRoute($this->baseUrl . "/create", self::REQUEST_POST, $cardCollectionCreateRequest);
        $this->assertSame(Response::HTTP_CREATED, $status);
    }

    /**
     * @return CardCollection
     * @throws EntityNotFoundException
     */
    public function getTestCardCollection(): CardCollection
    {
        self::initiateClient();
        $userRepository = self::$client->getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(["username" => self::$userCredentialByRoleArray["user"]["username"]]);
        if ($user === NULL) {
            throw new EntityNotFoundException("User test not found, maybe you forgot to load fixtures ??");
        }
        $cardCollectionRepository = self::$client->getContainer()->get(CardCollectionRepository::class);
        $cardCollections = $cardCollectionRepository->findBy(["user" => $user]);
        if (empty($cardCollections) === TRUE) {
            throw new EntityNotFoundException("CardCollection test not found, maybe you forgot to run testCardCollectionCreateWithAuth ??");
        }
        return $cardCollections[0];
    }

    /**
     * @depends testCardCollectionCreateWithAuth
     * @group card-collection-get-info
     * @throws JsonException
     * @throws EntityNotFoundException
     */
    public function testCardCollectionGetInfoWithAuth(): void
    {
        $cardCollection = $this->getTestCardCollection();
        [
            "status" => $status,
            "content" => $content,
        ] = self::runProtectedRoute($this->baseUrl . "/info/" . $cardCollection->getId());
        $this->assertSame(Response::HTTP_OK, $status);
        $this->assertNotEmpty($content["data"]["collection"]);
        $this->assertSame($content["data"]["collection"]["id"], $cardCollection->getId());
    }

    /**
     * @depends testCardCollectionCreateWithAuth
     * @group card-collection-update-public
     * @return void
     * @throws EntityNotFoundException
     * @throws JsonException
     */
    public function testCardCollectionUpdatePublic(): void
    {
        $cardCollection = $this->getTestCardCollection();
        $isPublicBeforeUpdate = $cardCollection->isIsPublic();
        $publicValue = ($isPublicBeforeUpdate === TRUE) ? 0 : 1;
        $url = sprintf("%s/update-public/%d/%d", $this->baseUrl, $cardCollection->getId(), $publicValue);
        [
            "status" => $status,
            "content" => $content,
        ] = self::runProtectedRoute($url, self::REQUEST_PUT);
        $this->assertSame(Response::HTTP_OK, $status);
        $this->assertNotEmpty($content["data"]["collection"]);
        $this->assertSame($content["data"]["collection"]["isPublic"], !$isPublicBeforeUpdate);
    }

    /**
     * @depends testCardCollectionCreateWithAuth
     * @group card-collection-update-info
     * @return void
     * @throws EntityNotFoundException
     * @throws JsonException
     */
    public function testCardCollectionUpdateInfo(): void
    {
        $cardCollection = $this->getTestCardCollection();
        $cardCollectionNameBeforeUpdate = $cardCollection->getName();
        $cardCardCollectionCountBeforeUpdate = $cardCollection->getCardCardCollections()->count();
        $url = sprintf("%s/edit/%d", $this->baseUrl, $cardCollection->getId());
        $cardCollectionCreateRequest = $this->getCardCollectionCreateRequest();
        $cardCollectionCreateRequest["name"] = "collection-test-bis";
        array_pop($cardCollectionCreateRequest["card-collection"]);
        [
            "status" => $status
        ] = self::runProtectedRoute($url, self::REQUEST_POST, $cardCollectionCreateRequest);
        $this->assertSame(Response::HTTP_OK, $status);
        [
            "status" => $status,
            "content" => $content
        ] = self::runProtectedRoute($this->baseUrl . "/info/" . $cardCollection->getId());
        $cardCollectionData = $content["data"]["collection"];
        $this->assertSame(Response::HTTP_OK, $status);
        $this->assertNotEmpty($cardCollectionData);
        $this->assertNotSame($cardCollectionData["name"], $cardCollectionNameBeforeUpdate);
        $this->assertNotSame(count($cardCollectionData["cardCardCollections"]), $cardCardCollectionCountBeforeUpdate);
    }

    /**
     * @depends testCardCollectionCreateWithAuth
     * @group card-collection-delete
     * @return void
     * @throws EntityNotFoundException
     * @throws JsonException
     */
    public function testCardCollectionDelete(): void
    {
        $cardCollection = $this->getTestCardCollection();
        $url = sprintf("%s/delete/%d", $this->baseUrl, $cardCollection->getId());
        [
            "status" => $status
        ] = self::runProtectedRoute($url, self::REQUEST_DELETE);
        $this->assertSame(Response::HTTP_OK, $status);
        [
            "status" => $status,
            "content" => $content
        ] = self::runProtectedRoute($this->baseUrl . "/info/" . $cardCollection->getId());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $status);
        $this->assertEmpty($content["data"]["collection"]);
    }
}
