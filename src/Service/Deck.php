<?php

namespace App\Service;

use App\Entity\CardExtraDeck;
use App\Entity\CardMainDeck;
use App\Entity\CardSideDeck;
use App\Service\Tool\Deck\ORM as DeckORMService;
use App\Service\Card as CardService;
use App\Entity\Deck as DeckEntity;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Deck
{
    private ParameterBagInterface $param;
    private CustomGeneric $customGenericService;

    private DeckORMService $deckORMService;

    public function __construct(
        ParameterBagInterface $param,
        CustomGeneric $customGenericService,
        DeckORMService $deckORMService
    )
    {
        $this->param = $param;
        $this->customGenericService = $customGenericService;
        $this->deckORMService = $deckORMService;
    }

    /**
     * @param DeckEntity $deck
     * @param array $deckCardArray
     * @param Card $cardService
     * @return DeckEntity
     */
    public function createDeckFromDeckCard(
        DeckEntity $deck,
        array $deckCardArray,
        CardService $cardService
    ): DeckEntity
    {
        $cardORMService = $cardService->getORMService();
        $mainDeckName = $this->param->get("MAIN_DECK_NAME");
        $extraDeckName = $this->param->get("EXTRA_DECK_NAME");
        $sideDeckName = $this->param->get("SIDE_DECK_NAME");
        $nbMaxSameCard = $this->param->get("NB_MAX_SAME_CARD_DECK");
        $deckCardUniqueArray = [];
        $countArray = [
            $mainDeckName => 0,
            $extraDeckName => 0,
            $sideDeckName => 0
        ];
        $deckArtwork = $deckCardArray["artwork"];
        if ($deckArtwork !== NULL) {
            $cardToUse = $cardORMService->findById($deckArtwork);
            if ($cardToUse !== NULL) {
                $cardPictures = $cardToUse->getPictures();
                if ($cardPictures->count() !== 0) {
                    $cardPictureToUse = $cardPictures[0];
                    $deck->setArtwork($cardPictureToUse);
                }
            }
        }
        unset($deckCardArray["artwork"]);
        foreach ($deckCardArray as $fieldType => $cardInfoArray) {
            foreach ($cardInfoArray as $cardInfo) {
                [
                    "id" => $cardInfoId,
                    "nbCopie" => $cardNbCopie,
                ] = $cardInfo;
                $cardInfoId = (int)$cardInfoId;
                $cardNbCopie = (int)$cardNbCopie;
                if ($cardNbCopie > $nbMaxSameCard) {
                    //@todo: add to logger
                    continue;
                }
                $cardEntity = $cardORMService->findById($cardInfoId);
                if ($cardEntity === NULL) {
                    //@todo: add to logger
                    continue;
                }
                if ($fieldType === $mainDeckName) {
                    $cardMainDeck = new CardMainDeck();
                    $cardMainDeck->addCard($cardEntity)
                        ->setNbCopie($cardNbCopie)
                        ->setDeck($deck);
                    $cardEntity->addCardMainDeck($cardMainDeck);
                    $deck->addCardMainDeck($cardMainDeck);
                    $this->deckORMService->persist($cardMainDeck);
                    $countArray[$mainDeckName] += $cardNbCopie;
                }
                if ($fieldType === $extraDeckName) {
                    $cardExtraDeck = new CardExtraDeck();
                    $cardExtraDeck->addCard($cardEntity)
                        ->setNbCopie($cardNbCopie)
                        ->setDeck($deck);
                    $cardEntity->addCardExtraDeck($cardExtraDeck);
                    $deck->addCardExtraDeck($cardExtraDeck);
                    $this->deckORMService->persist($cardExtraDeck);
                    $countArray[$extraDeckName] += $cardNbCopie;
                }
                if ($fieldType === $sideDeckName) {
                    $cardSideDeck = new CardSideDeck();
                    $cardSideDeck->addCard($cardEntity)
                        ->setNbCopie($cardNbCopie)
                        ->setDeck($deck);
                    $cardEntity->addCardSideDeck($cardSideDeck);
                    $deck->addCardSideDeck($cardSideDeck);
                    $this->deckORMService->persist($cardSideDeck);
                    $countArray[$sideDeckName] += $cardNbCopie;
                }
                if (isset($deckCardUniqueArray[$cardInfoId]) === false) {
                    $deckCardUniqueArray[$cardInfoId] = $cardEntity;
                }
                $cardEntity->addDeck($deck);
                $this->deckORMService->persist($cardEntity);
            }
        }
        foreach ($deckCardUniqueArray as $card) {
            $deck->addCardsUnique($card);
        }
        return $deck;
    }

    /**
     * @param string $jwt
     * @param array $parameter
     * @param Card $cardService
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     */
    public function create(string $jwt, array $parameter, CardService $cardService): array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse()];
        try {
            $user = $this->customGenericService->customGenericCheckJwt($jwt);
            if ($user === NULL) {
                $response["error"] = "No user found.";
                return $response;
            }
            [
                "name" => $name,
                "isPublic" => $isPublic,
                "artwork" => $artwork,
                "deck-card" => $deckCardArray
            ] = $parameter;
            $deck = new DeckEntity();
            $deck->setName($name)
                ->setSlugName($this->customGenericService->slugify($name))
                ->setIsPublic($isPublic)
                ->setUser($user);
            $user->addDeck($deck);
            $deckCardArray["artwork"] = $artwork;
            $deck = $this->createDeckFromDeckCard($deck, $deckCardArray, $cardService);
            $this->deckORMService->persist($deck);
            $this->deckORMService->persist($user);
            $this->deckORMService->flush();
        } catch (Exception $e) {
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while creating Deck.";
        }
        return $response;
    }

    /**
     * @param string $jwt
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     * "deck" => array[mixed]
     */
    public function listFromUser(string $jwt): array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse(), "deck" => []];
        try {
            $user = $this->customGenericService->customGenericCheckJwt($jwt);
            if ($user === NULL) {
                $response["error"] = "No user found.";
                return $response;
            }
            $deck = $this->deckORMService->findByUser($user);
            $response["deck"] = $this->customGenericService->getInfoSerialize($deck, ["deck_user_list"]);
        } catch (Exception $e) {
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while listing your Decks.";
        }
        return $response;
    }
}