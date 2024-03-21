<?php

namespace App\Service\Tool\Deck;

use App\Entity\CardExtraDeck as CardExtraDeckEntity;
use App\Entity\CardMainDeck as CardMainDeckEntity;
use App\Entity\CardPicture;
use App\Entity\CardSideDeck as CardSideDeckEntity;
use App\Service\Logger as LoggerService;
use App\Service\Tool\Deck\ORM as DeckORMService;
use App\Service\Tool\Card\ORM as CardORMService;
use App\Entity\Deck as DeckEntity;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Entity
{
    private LoggerService $loggerService;

    public function __construct(LoggerService $loggerService)
    {
        $this->loggerService = $loggerService;
        $this->loggerService->setIsCron(FALSE);
    }

    /**
     * Remove all card of DeckEntity
     * @param DeckEntity $deck
     * @param ORM $deckORMService
     * @return DeckEntity
     */
    public function removeCardDeck(DeckEntity $deck, DeckORMService $deckORMService): DeckEntity
    {
        $deckCardUniqueArray = $deck->getCardsUnique();
        $deckCardMainDeckArray = $deck->getCardMainDecks();
        $deckCardExtraDeckArray = $deck->getCardExtraDecks();
        $deckCardSideDeckArray = $deck->getCardSideDecks();
        foreach ($deckCardUniqueArray as $card) {
            $card->removeDeck($deck);
            $deck->removeCardsUnique($card);
            $deckORMService->persist($card);
        }
        foreach ($deckCardMainDeckArray as $cardMainDeck) {
            $deck->removeCardMainDeck($cardMainDeck);
            $deckORMService->remove($cardMainDeck);
        }
        foreach ($deckCardExtraDeckArray as $cardExtraDeck) {
            $deck->removeCardExtraDeck($cardExtraDeck);
            $deckORMService->remove($cardExtraDeck);
        }
        foreach ($deckCardSideDeckArray as $cardSideDeck) {
            $deck->removeCardSideDeck($cardSideDeck);
            $deckORMService->remove($cardSideDeck);
        }
        return $deck;
    }

    /**
     * @param int|null $deckArtwork
     * @param CardORMService $cardORMService
     * @return CardPicture|null
     */
    public function findCardPictureFromDeckArtworkValue(
        ?int $deckArtwork,
        CardORMService $cardORMService
    ): ?CardPicture
    {
        $cardPictureToUse = NULL;
        if ($deckArtwork !== NULL) {
            $cardToUse = $cardORMService->findById($deckArtwork);
            if ($cardToUse !== NULL) {
                $cardPictures = $cardToUse->getPictures();
                if ($cardPictures->count() !== 0) {
                    $cardPictureToUse = $cardPictures[0];
                }
            }
        }
        return $cardPictureToUse;
    }

    /**
     * @param DeckEntity $deck
     * @param array $deckCardArray
     * @param CardORMService $cardORMService
     * @param ORM $deckORMService
     * @param ParameterBagInterface $param
     * @return DeckEntity
     */
    public function createDeckFromDeckCard(
        DeckEntity $deck,
        array $deckCardArray,
        CardORMService $cardORMService,
        DeckORMService $deckORMService,
        ParameterBagInterface $param
    ): DeckEntity
    {
        $mainDeckName = $param->get("MAIN_DECK_NAME");
        $extraDeckName = $param->get("EXTRA_DECK_NAME");
        $sideDeckName = $param->get("SIDE_DECK_NAME");
        $nbMaxSameCard = $param->get("NB_MAX_SAME_CARD_DECK");
        $deckCardUniqueArray = [];
        $countArray = [
            $mainDeckName => 0,
            $extraDeckName => 0,
            $sideDeckName => 0
        ];
        foreach ($deckCardArray as $fieldType => $cardInfoArray) {
            foreach ($cardInfoArray as $cardInfo) {
                [
                    "id" => $cardInfoId,
                    "nbCopie" => $cardNbCopie,
                ] = $cardInfo;
                $cardInfoId = (int)$cardInfoId;
                $cardNbCopie = (int)$cardNbCopie;
                if ($cardNbCopie > $nbMaxSameCard) {
                    $this->loggerService->setLevel(LoggerService::WARNING)
                        ->addLog(
                            sprintf("Trying to add more than %d the same card", $nbMaxSameCard),
                            TRUE
                        );
                    continue;
                }
                $cardEntity = $cardORMService->findById($cardInfoId);
                if ($cardEntity === NULL) {
                    $this->loggerService->setLevel(LoggerService::WARNING)
                        ->addLog(
                            sprintf("Card id => %d not found", $cardInfoId),
                            TRUE
                        );
                    continue;
                }
                if ($fieldType === $mainDeckName) {
                    $cardMainDeck = new CardMainDeckEntity();
                    $cardMainDeck->addCard($cardEntity)
                        ->setNbCopie($cardNbCopie)
                        ->setDeck($deck);
                    $cardEntity->addCardMainDeck($cardMainDeck);
                    $deck->addCardMainDeck($cardMainDeck);
                    $deckORMService->persist($cardMainDeck);
                    $countArray[$mainDeckName] += $cardNbCopie;
                }
                if ($fieldType === $extraDeckName) {
                    $cardExtraDeck = new CardExtraDeckEntity();
                    $cardExtraDeck->addCard($cardEntity)
                        ->setNbCopie($cardNbCopie)
                        ->setDeck($deck);
                    $cardEntity->addCardExtraDeck($cardExtraDeck);
                    $deck->addCardExtraDeck($cardExtraDeck);
                    $deckORMService->persist($cardExtraDeck);
                    $countArray[$extraDeckName] += $cardNbCopie;
                }
                if ($fieldType === $sideDeckName) {
                    $cardSideDeck = new CardSideDeckEntity();
                    $cardSideDeck->addCard($cardEntity)
                        ->setNbCopie($cardNbCopie)
                        ->setDeck($deck);
                    $cardEntity->addCardSideDeck($cardSideDeck);
                    $deck->addCardSideDeck($cardSideDeck);
                    $deckORMService->persist($cardSideDeck);
                    $countArray[$sideDeckName] += $cardNbCopie;
                }
                if (isset($deckCardUniqueArray[$cardInfoId]) === false) {
                    $deckCardUniqueArray[$cardInfoId] = $cardEntity;
                }
                $cardEntity->addDeck($deck);
                $deckORMService->persist($cardEntity);
            }
        }
        foreach ($deckCardUniqueArray as $card) {
            $deck->addCardsUnique($card);
        }
        return $deck;
    }
}