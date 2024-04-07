<?php

namespace App\Tests\Service;

use App\Repository\CardCollectionRepository;
use App\Repository\CardRepository;
use App\Repository\CountryRepository;
use App\Repository\UserRepository;
use App\Service\Card;
use App\Service\CardCollection;
use App\Service\Country;
use App\Service\Tool\CardCollection\ORM;
use Doctrine\ORM\EntityNotFoundException;

class CardCollectionServiceTest extends AbstractTestService
{
    private CardCollection $service;

    public function setUp(): void
    {
        $this->service = self::getService(CardCollection::class);
        parent::setUp();
    }

    public function testCardCollectionGetORMService():void
    {
        $this->assertInstanceOf(ORM::class, $this->service->getORMService());
    }

    /**
     * @return array
     * @throws EntityNotFoundException
     */
    public function getCardCollectionCreateParam(): array
    {
        $cardRepository = self::getService(CardRepository::class);
        $countryRepository = self::getService(CountryRepository::class);
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
            "name" => "collection-test-service",
            "isPublic" => "true",
            "artwork" => NULL,
            "card-collection" => $cardCollectionData
        ];
    }

    /**
     * @group card-collection-check-user
     * @group card-collection-get-info
     * @group card-collection-update-public
     * @group card-collection-update-info
     * @return void
     * @throws EntityNotFoundException
     */
    public function testCardCollectionCreate(): void
    {
        $jwt = self::getJWT();
        $cardCollectionCreateParam = $this->getCardCollectionCreateParam();
        $countryService = self::getService(Country::class);
        $cardService = self::getService(Card::class);
        [
            "error" => $error,
        ] = $this->service->create($jwt, $cardCollectionCreateParam, $cardService, $countryService);
        $this->assertEmpty($error);
    }

    /**
     * @return \App\Entity\CardCollection
     * @throws EntityNotFoundException
     */
    public function getTestCardCollection(): \App\Entity\CardCollection
    {
        $userRepository = self::getService(UserRepository::class);
        $user = $userRepository->findOneBy(["username" => self::$userCredentialByRoleArray["user"]["username"]]);
        if ($user === NULL) {
            throw new EntityNotFoundException("User test not found, maybe you forgot to load fixtures ??");
        }
        $cardCollectionRepository = self::getService(CardCollectionRepository::class);
        $cardCollections = $cardCollectionRepository->findBy(["user" => $user]);
        if (empty($cardCollections) === TRUE) {
            throw new EntityNotFoundException("CardCollection test not found, maybe you forgot to run testCardCollectionCreate ??");
        }
        return $cardCollections[0];
    }

    /**
     * @group card-collection-check-user
     * @return void
     * @throws EntityNotFoundException
     */
    public function testCardCollectionCheckUserAndCollection(): void
    {
        $jwt = self::getJWT();
        $cardCollection = $this->getTestCardCollection();
        [
            "error" => $error,
            "user" => $user,
            "collection" => $duplicateCardCollection
        ] = $this->service->checkUserAndCollection($jwt, $cardCollection->getId());
        $this->assertEmpty($error);
        $this->assertSame($user->getUsername(), self::$userCredentialByRoleArray["user"]["username"]);
        $this->assertSame($duplicateCardCollection->getId(), $cardCollection->getId());
    }

    /**
     * @depends testCardCollectionCreate
     * @group card-collection-get-info
     * @return void
     * @throws EntityNotFoundException
     */
    public function testCardCollectionGetInfo(): void
    {
        $jwt = self::getJWT();
        $cardCollection = $this->getTestCardCollection();
        [
            "error" => $error,
            "collection" => $duplicateCardCollection
        ] = $this->service->getInfo($jwt, $cardCollection->getId());
        $this->assertEmpty($error);
        $this->assertSame($duplicateCardCollection["id"], $cardCollection->getId());
    }

    /**
     * @depends testCardCollectionCreate
     * @group card-collection-update-public
     * @return void
     * @throws EntityNotFoundException
     */
    public function testCardCollectionUpdatePublic(): void
    {
        $jwt = self::getJWT();
        $cardCollection = $this->getTestCardCollection();
        $isPublicBeforeUpdate = $cardCollection->isIsPublic();
        $publicValue = ($isPublicBeforeUpdate === TRUE) ? 0 : 1;
        [
            "error" => $error,
            "collection" => $updatedCardCollection
        ] = $this->service->updatePublic($jwt, $cardCollection->getId(), $publicValue);
        $this->assertEmpty($error);
        $this->assertSame($updatedCardCollection["id"], $cardCollection->getId());
        $this->assertSame(!$isPublicBeforeUpdate, $updatedCardCollection["isPublic"]);
    }

    /**
     * @depends testCardCollectionCreate
     * @group card-collection-update-info
     * @return void
     * @throws EntityNotFoundException
     */
    public function testCardCollectionUpdateInfo(): void
    {
        $jwt = self::getJWT();
        $cardCollection = $this->getTestCardCollection();
        $cardCollectionNameBeforeUpdate = $cardCollection->getName();
        $cardCardCollectionCountBeforeUpdate = $cardCollection->getCardCardCollections()->count();
        $cardCollectionCreateParam = $this->getCardCollectionCreateParam();
        $cardCollectionCreateParam["name"] = "collection-test-bis";
        array_pop($cardCollectionCreateParam["card-collection"]);
        $countryService = self::getService(Country::class);
        $cardService = self::getService(Card::class);
        [
            "error" => $error,
        ] = $this->service->update(
            $jwt,
            $cardCollection->getId(),
            $cardCollectionCreateParam,
            $cardService,
            $countryService
        );
        $this->assertEmpty($error);
        [
            "error" => $error,
            "collection" => $duplicateCardCollection
        ] = $this->service->getInfo($jwt, $cardCollection->getId());
        $this->assertEmpty($error);
        $this->assertSame($duplicateCardCollection["id"], $cardCollection->getId());
        $this->assertNotSame($duplicateCardCollection["name"], $cardCollectionNameBeforeUpdate);
        $this->assertNotSame(count($duplicateCardCollection["cardCardCollections"]), $cardCardCollectionCountBeforeUpdate);
    }

    /**
     * @depends testCardCollectionCreate
     * @group card-collection-delete
     * @return void
     * @throws EntityNotFoundException
     */
    public function testCardCollectionDelete(): void
    {
        $jwt = self::getJWT();
        $cardCollectionId = $this->getTestCardCollection()->getId();
        [
            "error" => $error,
        ] = $this->service->deleteFromId($jwt, $cardCollectionId);
        $this->assertEmpty($error);
        [
            "error" => $error,
            "collection" => $duplicateCardCollection
        ] = $this->service->getInfo($jwt, $cardCollectionId);
        $this->assertNotEmpty($error);
        $this->assertEmpty($duplicateCardCollection);
    }
}
