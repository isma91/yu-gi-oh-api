<?php

namespace App\Service\Tool\CardCollection;

use App\Entity\CardCardCollection as CardCardCollectionEntity;
use App\Entity\CardCollection as CardCollectionEntity;
use App\Entity\CardSet as CardSetEntity;
use App\Service\Logger as LoggerService;
use App\Service\Tool\Card\ORM as CardORMService;
use App\Service\Tool\Country\ORM as CountryORMService;
use App\Service\Tool\CardCollection\ORM as CardCollectionORMService;

class Entity
{
    private LoggerService $loggerService;

    public function __construct(LoggerService $loggerService)
    {
        $this->loggerService = $loggerService;
        $this->loggerService->setIsCron(FALSE);
    }

    /**
     * @param CardSetEntity[] $cardSetArray
     * @param int $rarityId
     * @return array[
     * "set" => CardSetEntity,
     * "rarity" => RarityEntity|null
     * ]
     */
    public function findCardSetAndRarityFromCardSetArrayAndRarityId(array $cardSetArray, int $rarityId): array
    {
        $return = ["set" => $cardSetArray[0], "rarity" => NULL];
        if ($rarityId === 0) {
            return $return;
        }
        foreach ($cardSetArray as $cardSet) {
            $rarities = $cardSet->getRarities();
            foreach ($rarities as $rarity) {
                if ($rarity->getId() === $rarityId) {
                    $return["set"] = $cardSet;
                    $return["rarity"] = $rarity;
                    break 2;
                }
            }
        }
        return $return;
    }

    /**
     * @param CardCollectionEntity $cardCollectionEntity
     * @param array $cardCollectionArray
     * @param CardORMService $cardORMService
     * @param CountryORMService $countryORMService
     * @param CardCollectionORMService $cardCollectionORMService
     * @return CardCollectionEntity
     */
    public function createCardCardCollection(
        CardCollectionEntity $cardCollectionEntity,
        array $cardCollectionArray,
        CardORMService $cardORMService,
        CountryORMService $countryORMService,
        CardCollectionORMService $cardCollectionORMService
    ): CardCollectionEntity
    {
        [
            "artwork" => $artwork
        ] = $cardCollectionArray;
        unset($cardCollectionArray["artwork"]);
        $artworkFounded = $artwork === NULL;
        foreach ($cardCollectionArray as $cardCollectionInfo) {
            [
                "nbCopie" => $nbCopie,
                "picture" => $pictureId,
                "country" => $countryId,
                "set" => $setId,
                "rarity" => $rarityId,
                "card" => $cardId
            ] = $cardCollectionInfo;
            $nbCopie = (int)$nbCopie;
            $cardId = (int)$cardId;
            if ($nbCopie < 0) {
                $nbCopie = 1;
            }
            $cardEntity = $cardORMService->findById($cardId);
            if ($cardEntity === NULL) {
                $this->loggerService
                    ->setLevel(LoggerService::WARNING)
                    ->addLog(sprintf("Card not found with id => %s", $cardCollectionInfo["card"]));
                continue;
            }
            $countryId = (int)$countryId;
            $country = $countryORMService->findById($countryId);
            if ($country === NULL) {
                $this->loggerService
                    ->setLevel(LoggerService::WARNING)
                    ->addLog(sprintf("Country not found with id => %s", $cardCollectionInfo["country"]));
                continue;
            }
            $setId = (int)$setId;
            $cardSets = $cardEntity->getCardSets();
            $cardSetToUseArray = [];
            foreach ($cardSets as $cardSet) {
                $sets = $cardSet->getSets();
                if ($cardSet->getSets()->count() === 0) {
                    $this->loggerService
                        ->setLevel(LoggerService::WARNING)
                        ->addLog(sprintf("Not Set not found with in CardSet id => %d", $cardSet->getId()));
                    continue;
                }
                if ($sets[0]->getId() === $setId) {
                    $cardSetToUseArray[] = $cardSet;
                }
            }
            $cardCardCollection = new CardCardCollectionEntity();
            $cardCardCollection->setNbCopie($nbCopie)
                ->setCard($cardEntity)
                ->setCountry($country);
            if (empty($cardSetToUseArray) === FALSE) {
                $rarityId = (int)$rarityId;
                [
                    "set" => $cardSetToUse,
                    "rarity" => $rarityToUse
                ] = $this->findCardSetAndRarityFromCardSetArrayAndRarityId($cardSetToUseArray, $rarityId);
                $cardCardCollection->setCardSet($cardSetToUse->getSets()[0])
                    ->setRarity($rarityToUse);
                if ($rarityId > 0 && $rarityToUse === NULL) {
                    $this->loggerService
                        ->setLevel(LoggerService::WARNING)
                        ->addLog(
                            sprintf(
                                "Rarity not found with id => %s in CardSet id => %d",
                                $cardCollectionInfo["rarity"],
                                $cardSetToUse->getId(),
                            )
                        );
                }
            }
            $pictureId = (int)$pictureId;
            $cardPictures = $cardEntity->getPictures();
            $cardPictureToUse = NULL;
            foreach ($cardPictures as $cardPicture) {
                if ($cardPictureToUse !== NULL && $artworkFounded === TRUE) {
                    break;
                }
                $cardPictureId = $cardPicture->getId();
                if ($artworkFounded === FALSE && $cardPictureId === $artwork) {
                    $artworkFounded = TRUE;
                    $cardCollectionEntity->setArtwork($cardPicture);
                }
                if ($cardPictureId === $pictureId) {
                    $cardPictureToUse = $cardPicture;
                }
            }
            if ($cardPictureToUse === NULL) {
                if ($cardPictures->count() === 0) {
                    $this->loggerService
                        ->setLevel(LoggerService::WARNING)
                        ->addLog(
                            sprintf(
                                "Not CardPicture selected or found with id => %s also no CardPicture found for card id => %d",
                                $cardCollectionInfo["picture"],
                                $cardEntity->getId()
                            )
                        );
                    continue;
                }
                $cardPictureToUse = $cardPictures[0];
            }
            $cardCardCollection->setPicture($cardPictureToUse);
            $cardCollectionEntity->addCardCardCollection($cardCardCollection);
            $cardEntity->addCardCardCollection($cardCardCollection);
            $cardCollectionORMService->persist($cardCardCollection);
            $cardCollectionORMService->persist($cardEntity);
        }
        $cardCollectionORMService->persist($cardCollectionEntity);
        return $cardCollectionEntity;
    }

    /**
     * @param CardCollectionEntity $cardCollection
     * @param CardCollectionORMService $cardCollectionORMService
     * @return CardCollectionEntity
     */
    public function removeCardCardCollection(
        CardCollectionEntity $cardCollection,
        CardCollectionORMService $cardCollectionORMService
    ): CardCollectionEntity
    {
        $cardCardCollections = $cardCollection->getCardCardCollections();
        foreach ($cardCardCollections as $cardCardCollection) {
            $card = $cardCardCollection->getCard();
            if ($card === NULL) {
                $this->loggerService->setLevel(LoggerService::WARNING)
                    ->setIsCron(FALSE)
                    ->addLog(
                        sprintf(
                            "No Card found in CardCardCollection id => %d",
                            $cardCardCollection->getId()
                        )
                    );
            } else {
                $card->removeCardCardCollection($cardCardCollection);
            }
            $cardCollectionORMService->persist($card);
            $cardCollection->removeCardCardCollection($cardCardCollection);
            $cardCollectionORMService->remove($cardCardCollection);
        }
        return $cardCollection;
    }
}