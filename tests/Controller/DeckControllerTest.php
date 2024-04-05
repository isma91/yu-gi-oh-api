<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Deck;
use App\Repository\CardRepository;
use App\Repository\DeckRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityNotFoundException;
use JsonException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;

class DeckControllerTest extends AbstractWebTestCase
{
    private string $baseUrl = "/deck";

    /**
     * @throws JsonException
     */
    public function testDeckBase(): void
    {
        static::expectRouteNotFound($this->baseUrl);
    }

    /**
     * Needed twice because we use it also in the update deck
     * @return array
     * @throws EntityNotFoundException
     */
    public function getDeckCreateRequest(): array
    {
        self::initiateClient();
        //deck all fieldType
        $param = self::$client->getContainer()->get(ParameterBagInterface::class);
        $mainDeckName = $param->get("MAIN_DECK_NAME");
        $extraDeckName = $param->get("EXTRA_DECK_NAME");
        $sideDeckName = $param->get("SIDE_DECK_NAME");
        //take some card
        $cardRepository = self::$client->getContainer()->get(CardRepository::class);
        $cosmoQueen = $cardRepository->findOneBy(["slugName" => "cosmo-queen"]);
        $ashBlossom = $cardRepository->findOneBy(["slugName" => "ash-blossom-and-joyous-spring"]);
        $accesscodeTalker = $cardRepository->findOneBy(["slugName" => "accesscode-talker"]);
        //check that all these card exist
        if ($cosmoQueen === NULL) {
            throw new EntityNotFoundException(
                "Card 'Cosmo Queen' not found, maybe you don't run the Import before testing ??"
            );
        }
        if ($ashBlossom === NULL) {
            throw new EntityNotFoundException(
                "Card 'Ash Blossom and Joyous Spring' not found, maybe you don't run the Import before testing ??"
            );
        }
        if ($accesscodeTalker === NULL) {
            throw new EntityNotFoundException(
                "Card 'Accesscode Talker' not found, maybe you don't run the Import before testing ??"
            );
        }
        //fulfill the request param to create a deck
        return [
            "name" => "deck-test",
            "isPublic" => FALSE,
            "deck-card" => [
                $mainDeckName => [
                    ["id" => $cosmoQueen->getId(), "nbCopie" => 3],
                    ["id" => $ashBlossom->getId(), "nbCopie" => 3],
                ],
                $extraDeckName => [
                    ["id" => $accesscodeTalker->getId(), "nbCopie" => 3]
                ],
                $sideDeckName => [
                    ["id" => $accesscodeTalker->getId(), "nbCopie" => 1],
                    ["id" => $ashBlossom->getId(), "nbCopie" => 2],
                ]
            ]
        ];
    }

    /**
     * @group deck-get-info
     * @group deck-update-public
     * @group deck-update-info
     * @group deck-delete
     * @throws EntityNotFoundException
     * @throws JsonException
     */
    public function testDeckCreate(): void
    {
        $deckCreateRequestParam = $this->getDeckCreateRequest();
        [
            "status" => $status
        ] = self::runProtectedRoute(
            $this->baseUrl . "/create",
            self::REQUEST_POST,
            $deckCreateRequestParam
        );
        $this->assertSame(Response::HTTP_CREATED, $status);
    }

    /**
     * Return the Deck test created by the User test
     * @return Deck
     * @throws EntityNotFoundException
     */
    public function getTestDeck(): Deck
    {
        self::initiateClient();
        $userRepository = self::$client->getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(["username" => self::$userCredentialByRoleArray["user"]["username"]]);
        if ($user === NULL) {
            throw new EntityNotFoundException("User test not found, maybe you forgot to load fixtures ??");
        }
        $deckRepository = self::$client->getContainer()->get(DeckRepository::class);
        $decks = $deckRepository->findBy(["user" => $user]);
        if (empty($decks) === TRUE) {
            throw new EntityNotFoundException("Deck test not found, maybe you forgot to run testDeckCreate ??");
        }
        return $decks[0];
    }

    /**
     * @group deck-get-info
     * @return void
     * @throws EntityNotFoundException
     * @throws JsonException
     */
    public function testDeckGetInfoWithAuth(): void
    {
        $deck = $this->getTestDeck();
        [
            "status" => $status,
            "content" => $content
        ] = self::runProtectedRoute($this->baseUrl . "/info/" . $deck->getId());
        $this->assertSame(Response::HTTP_OK, $status);
        $this->assertNotEmpty($content["data"]["deck"]);
        $this->assertSame($content["data"]["deck"]["id"], $deck->getId());
    }

    /**
     * @group deck-get-info
     * @return void
     * @throws EntityNotFoundException
     * @throws JsonException
     */
    public function testDeckGetInfoWithoutAuth(): void
    {
        $deck = $this->getTestDeck();
        self::expectRouteUnauthorized($this->baseUrl . "/info/" . $deck->getId());
    }

    /**
     * @group deck-update-public
     * @return void
     * @throws EntityNotFoundException
     * @throws JsonException
     */
    public function testDeckUpdatePublic(): void
    {
        $deck = $this->getTestDeck();
        $isPublicBeforeUpdate = $deck->isIsPublic();
        $publicValue = ($isPublicBeforeUpdate === TRUE) ? 0 : 1;
        $url = sprintf("%s/update-public/%d/%d", $this->baseUrl, $deck->getId(), $publicValue);
        [
            "status" => $status,
            "content" => $content,
        ] = self::runProtectedRoute($url, self::REQUEST_PUT);
        $this->assertSame(Response::HTTP_OK, $status);
        $this->assertNotEmpty($content["data"]["deck"]);
        $this->assertSame($content["data"]["deck"]["isPublic"], !$isPublicBeforeUpdate);
    }

    /**
     * @group deck-update-info
     * @return void
     * @throws EntityNotFoundException
     * @throws JsonException
     */
    public function testDeckUpdateInfo(): void
    {
        $deck = $this->getTestDeck();
        $deckNameBeforeUpdate = $deck->getName();
        $cardSideDeckCountBeforeUpdate = $deck->getCardSideDecks()->count();
        $url = sprintf("%s/edit/%d", $this->baseUrl, $deck->getId());
        $deckUpdateRequest = $this->getDeckCreateRequest();
        $param = self::$client->getContainer()->get(ParameterBagInterface::class);
        $sideDeckName = $param->get("SIDE_DECK_NAME");
        $deckUpdateRequest["name"] = "test-deck-bis";
        $deckUpdateRequest["deck-card"][$sideDeckName] = [];
        [
            "status" => $status
        ] = self::runProtectedRoute($url, self::REQUEST_POST, $deckUpdateRequest);
        $this->assertSame(Response::HTTP_OK, $status);
        [
            "status" => $status,
            "content" => $content
        ] = self::runProtectedRoute($this->baseUrl . "/info/" . $deck->getId());
        $deckData = $content["data"]["deck"];
        $this->assertSame(Response::HTTP_OK, $status);
        $this->assertNotEmpty($deckData);
        $this->assertNotSame($deckData["name"], $deckNameBeforeUpdate);
        $this->assertNotSame(count($deckData["cardSideDecks"]), $cardSideDeckCountBeforeUpdate);
    }

    /**
     * @group deck-delete
     * @return void
     * @throws EntityNotFoundException
     * @throws JsonException
     */
    public function testDeckDelete(): void
    {
        $deck = $this->getTestDeck();
        $url = sprintf("%s/delete/%d", $this->baseUrl, $deck->getId());
        [
            "status" => $status
        ] = self::runProtectedRoute($url, self::REQUEST_DELETE);
        $this->assertSame(Response::HTTP_OK, $status);
        [
            "status" => $status,
            "content" => $content
        ] = self::runProtectedRoute($this->baseUrl . "/info/" . $deck->getId());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $status);
        $this->assertEmpty($content["data"]["deck"]);
    }
}
