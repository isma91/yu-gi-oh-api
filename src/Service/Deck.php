<?php

namespace App\Service;

use App\Service\Tool\Deck\ORM as DeckORMService;
use App\Service\Tool\Deck\Entity as DeckEntityService;
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
    private DeckEntityService $deckEntityService;

    public function __construct(
        ParameterBagInterface $param,
        CustomGeneric $customGenericService,
        DeckORMService $deckORMService,
        DeckEntityService $deckEntityService
    )
    {
        $this->param = $param;
        $this->customGenericService = $customGenericService;
        $this->deckORMService = $deckORMService;
        $this->deckEntityService = $deckEntityService;
    }

    /**
     * @return DeckORMService
     */
    public function getORMService(): DeckORMService
    {
        return $this->deckORMService;
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
            $cardORMService = $cardService->getORMService();
            $deckArtwork = $this->deckEntityService
                ->findCardPictureFromDeckArtworkValue($artwork, $cardORMService);
            $deck = new DeckEntity();
            $deck->setName($name)
                ->setSlugName($this->customGenericService->slugify($name))
                ->setIsPublic($isPublic)
                ->setArtwork($deckArtwork)
                ->setUser($user);
            $user->addDeck($deck);
            $deck = $this->deckEntityService
                ->createDeckFromDeckCard(
                    $deck,
                    $deckCardArray,
                    $cardORMService,
                    $this->deckORMService,
                    $this->param
                );
            $this->deckORMService->persist($deck);
            $this->deckORMService->persist($user);
            $this->deckORMService->flush();
            $this->customGenericService->addInfoLogFromDebugBacktrace();
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while creating Deck.";
        }
        return $response;
    }

    /**
     * @param DeckEntity $deck
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     * "deck" => array[mixed]
     */
    public function getInfo(DeckEntity $deck): array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse(), "deck" => []];
        try {
            $response["deck"] = $this->customGenericService->getInfoSerialize([$deck], ["deck_info", "card_info"])[0];
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while getting Deck.";
        }
        return $response;
    }

    /**
     * @param DeckEntity $deck
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     */
    public function deleteFromId(DeckEntity $deck): array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse()];
        try {
            $deck = $this->deckEntityService->removeCardDeck($deck, $this->deckORMService);
            $this->deckORMService->remove($deck);
            $this->deckORMService->flush();
            $this->customGenericService->addInfoLogFromDebugBacktrace();
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while deleting Deck.";
        }
        return $response;
    }

    /**
     * @param DeckEntity $deck
     * @param int $public
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     * "deck" => array[mixed]
     */
    public function updatePublic(DeckEntity $deck, int $public): array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse(), "deck" => []];
        try {
            $deck->setIsPublic($publicValue);
            $this->deckORMService->persist($deck);
            $this->deckORMService->flush();
            $response["deck"] = $this->customGenericService->getInfoSerialize([$deck], ["deck_info", "card_info"])[0];
            $this->customGenericService->addInfoLogFromDebugBacktrace();
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while getting Deck.";
        }
        return $response;
    }

    /**
     * @param DeckEntity $deck
     * @param array $parameter
     * @param Card $cardService
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     */
    public function update(DeckEntity $deck, array $parameter, CardService $cardService): array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse()];
        try {
            [
                "name" => $name,
                "isPublic" => $isPublic,
                "artwork" => $artwork,
                "deck-card" => $deckCardArray
            ] = $parameter;
            $cardORMService = $cardService->getORMService();
            $deckArtwork = $this->deckEntityService
                ->findCardPictureFromDeckArtworkValue($artwork, $cardORMService);
            $deck->setName($name)
                ->setSlugName($this->customGenericService->slugify($name))
                ->setIsPublic($isPublic)
                ->setArtwork($deckArtwork);
            $deck = $this->deckEntityService->removeCardDeck($deck, $this->deckORMService);
            $deck = $this->deckEntityService
                ->createDeckFromDeckCard(
                    $deck,
                    $deckCardArray,
                    $cardORMService,
                    $this->deckORMService,
                    $this->param
                );
            $this->deckORMService->persist($deck);
            $this->deckORMService->flush();
            $this->customGenericService->addInfoLogFromDebugBacktrace();
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while updating Deck.";
        }
        return $response;
    }
}