<?php

namespace App\Tests\Service;

use App\Repository\CardRepository;
use App\Repository\DeckRepository;
use App\Repository\UserRepository;
use App\Service\Card;
use App\Service\Deck;
use App\Service\Tool\Deck\ORM;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DeckServiceTest extends AbstractTestService
{
    private Deck $service;

    public function setUp(): void
    {
        $this->service = self::getService(Deck::class);
        parent::setUp();
    }

    public function testDeckGetORMService():void
    {
        $this->assertInstanceOf(ORM::class, $this->service->getORMService());
    }

    /**
     * @return array
     * @throws EntityNotFoundException
     */
    public function getDeckCreateParam(): array
    {
        $param = self::getService(ParameterBagInterface::class);
        $mainDeckName = $param->get("MAIN_DECK_NAME");
        $extraDeckName = $param->get("EXTRA_DECK_NAME");
        $sideDeckName = $param->get("SIDE_DECK_NAME");
        //take some card
        $cardRepository = self::getService(CardRepository::class);
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
            "artwork" => NULL,
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
     * @group deck-service-check-user
     * @group deck-service-get-info
     * @group deck-service-update-public
     * @group deck-service-update-info
     * @return void
     * @throws EntityNotFoundException
     */
    public function testDeckCreate(): void
    {
        $jwt = self::getJWT();
        $deckCreateParam = $this->getDeckCreateParam();
        $cardService = self::getService(Card::class);
        [
            "error" => $error,
        ] = $this->service->create($jwt, $deckCreateParam, $cardService);
        $this->assertEmpty($error);
    }

    /**
     * @return \App\Entity\Deck
     * @throws EntityNotFoundException
     */
    public function getTestDeck(): \App\Entity\Deck
    {
        $userRepository = self::getService(UserRepository::class);
        $user = $userRepository->findOneBy(["username" => self::$userCredentialByRoleArray["user"]["username"]]);
        if ($user === NULL) {
            throw new EntityNotFoundException("User test not found, maybe you forgot to load fixtures ??");
        }
        $deckRepository = self::getService(DeckRepository::class);
        $decks = $deckRepository->findBy(["user" => $user]);
        if (empty($decks) === TRUE) {
            throw new EntityNotFoundException("Deck test not found, maybe you forgot to run testDeckCreate ??");
        }
        return $decks[0];
    }

    /**
     * @depends testDeckCreate
     * @group deck-service-check-user
     * @return void
     * @throws EntityNotFoundException
     */
    public function testDeckCheckUserAndDeck(): void
    {
        $jwt = self::getJWT();
        $deck = $this->getTestDeck();
        [
            "error" => $error,
            "user" => $user,
            "deck" => $duplicateDeck
        ] = $this->service->checkUserAndDeck($jwt, $deck->getId());
        $this->assertEmpty($error);
        $this->assertSame($user->getUsername(), self::$userCredentialByRoleArray["user"]["username"]);
        $this->assertSame($duplicateDeck->getId(), $deck->getId());
    }

    /**
     * @depends testDeckCreate
     * @group deck-service-get-info
     * @return void
     * @throws EntityNotFoundException
     */
    public function testDeckGetInfo(): void
    {
        $jwt = self::getJWT();
        $deck = $this->getTestDeck();
        [
            "error" => $error,
            "deck" => $duplicateDeck
        ] = $this->service->getInfo($jwt, $deck->getId());
        $this->assertEmpty($error);
        $this->assertSame($duplicateDeck["id"], $deck->getId());
    }

    /**
     * @depends testDeckCreate
     * @group deck-service-update-public
     * @return void
     * @throws EntityNotFoundException
     */
    public function testDeckUpdatePublic(): void
    {
        $jwt = self::getJWT();
        $deck = $this->getTestDeck();
        $isPublicBeforeUpdate = $deck->isIsPublic();
        $publicValue = ($isPublicBeforeUpdate === TRUE) ? 0 : 1;
        [
            "error" => $error,
            "deck" => $updatedDeck
        ] = $this->service->updatePublic($jwt, $deck->getId(), $publicValue);
        $this->assertEmpty($error);
        $this->assertSame($updatedDeck["id"], $deck->getId());
        $this->assertSame(!$isPublicBeforeUpdate, $updatedDeck["isPublic"]);
    }

    /**
     * @depends testDeckCreate
     * @group deck-service-update-info
     * @return void
     * @throws EntityNotFoundException
     */
    public function testDeckUpdateInfo(): void
    {
        $jwt = self::getJWT();
        $deck = $this->getTestDeck();
        $deckNameBeforeUpdate = $deck->getName();
        $cardSideDeckCountBeforeUpdate = $deck->getCardSideDecks()->count();
        $deckUpdateRequest = $this->getDeckCreateParam();
        $param = self::getService(ParameterBagInterface::class);
        $sideDeckName = $param->get("SIDE_DECK_NAME");
        $deckUpdateRequest["name"] = "test-deck-bis";
        $deckUpdateRequest["deck-card"][$sideDeckName] = [];
        $cardService = self::getService(Card::class);
        [
            "error" => $error,
        ] = $this->service->update(
            $jwt,
            $deck->getId(),
            $deckUpdateRequest,
            $cardService,
        );
        $this->assertEmpty($error);
        [
            "error" => $error,
            "deck" => $duplicateDeck
        ] = $this->service->getInfo($jwt, $deck->getId());
        $this->assertEmpty($error);
        $this->assertSame($duplicateDeck["id"], $deck->getId());
        $this->assertNotSame($duplicateDeck["name"], $deckNameBeforeUpdate);
        $this->assertNotSame(count($duplicateDeck["cardSideDecks"]), $cardSideDeckCountBeforeUpdate);
    }

    /**
     * @depends testDeckCreate
     * @group deck-service-delete
     * @return void
     * @throws EntityNotFoundException
     */
    public function testDeckDelete(): void
    {
        $jwt = self::getJWT();
        $deckId = $this->getTestDeck()->getId();
        [
            "error" => $error,
        ] = $this->service->deleteFromId($jwt, $deckId);
        $this->assertEmpty($error);
        [
            "error" => $error,
            "deck" => $duplicateDeck
        ] = $this->service->getInfo($jwt, $deckId);
        $this->assertNotEmpty($error);
        $this->assertEmpty($duplicateDeck);
    }
}
