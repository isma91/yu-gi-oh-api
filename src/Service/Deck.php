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
     * @param string $jwt
     * @param int $id
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     * "user" => null|UserEntity,
     * "deck" => null|DeckEntity,
     * ]
     */
    public function checkUserAndDeck(string $jwt, int $id): array
    {
        $response = [
            ...$this->customGenericService->getEmptyReturnResponse(),
            "user" => NULL,
            "deck" => NULL
        ];
        $user = $this->customGenericService->customGenericCheckJwt($jwt);
        if ($user === NULL) {
            $response["error"] = "No user found.";
            return $response;
        }
        $response["user"] = $user;
        $deck = $this->deckORMService->findById($id);
        if ($deck === NULL) {
            $response["error"] = "Deck not found.";
            return $response;
        }
        $deckUser = $deck->getUser();
        if ($deckUser === NULL) {
            $this->customGenericService->addErrorMessageLog(
                sprintf(
                    "No User found for Deck id => %d", $deck->getId()
                )
            );
            $response["error"] = "Deck not available.";
            return $response;
        }
        $response["deck"] = $deck;
        return $response;
    }

    /**
     * @param string $jwt
     * @param int $id
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     * "deck" => array[mixed]
     */
    public function getInfo(string $jwt, int $id): array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse(), "deck" => []];
        try {
            [
                "error" => $errorCheckUserDeck,
                "errorDebug" => $errorDebugCheckUserDeck,
                "user" => $user,
                "deck" => $deck,
            ] = $this->checkUserAndDeck($jwt, $id);
            if (empty($errorCheckUserDeck) === FALSE) {
                $response["error"] = $errorCheckUserDeck;
                $response["errorDebug"] = $errorDebugCheckUserDeck;
                return $response;
            }
            $deckUser = $deck->getUser();
            $isAdmin = $this->customGenericService->checkIfUserIsAdmin($user);
            if ($isAdmin === FALSE && $deck->isIsPublic() === FALSE && $deckUser->getId() !== $user->getId()) {
                $response["error"] = "Deck not available.";
                return $response;
            }
            $response["deck"] = $this->customGenericService->getInfoSerialize([$deck], ["deck_info", "card_info"])[0];
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while getting Deck.";
        }
        return $response;
    }

    /**
     * @param string $jwt
     * @param int $id
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     */
    public function deleteFromId(string $jwt, int $id): array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse()];
        try {
            [
                "error" => $errorCheckUserDeck,
                "errorDebug" => $errorDebugCheckUserDeck,
                "user" => $user,
                "deck" => $deck,
            ] = $this->checkUserAndDeck($jwt, $id);
            if (empty($errorCheckUserDeck) === FALSE) {
                $response["error"] = $errorCheckUserDeck;
                $response["errorDebug"] = $errorDebugCheckUserDeck;
                return $response;
            }
            $deckUser = $deck->getUser();
            $isAdmin = $this->customGenericService->checkIfUserIsAdmin($user);
            if ($isAdmin === FALSE && $deckUser->getId() !== $user->getId()) {
                $response["error"] = "Deck not available.";
                return $response;
            }
            $deck = $this->deckEntityService->removeCardDeck($deck, $this->deckORMService);
            $user->removeDeck($deck);
            $this->deckORMService->persist($user);
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
     * @param string $jwt
     * @param int $id
     * @param int $public
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     * "deck" => array[mixed]
     */
    public function updatePublic(string $jwt, int $id, int $public): array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse(), "deck" => []];
        try {
            [
                "error" => $errorCheckUserDeck,
                "errorDebug" => $errorDebugCheckUserDeck,
                "user" => $user,
                "deck" => $deck,
            ] = $this->checkUserAndDeck($jwt, $id);
            if (empty($errorCheckUserDeck) === FALSE) {
                $response["error"] = $errorCheckUserDeck;
                $response["errorDebug"] = $errorDebugCheckUserDeck;
                return $response;
            }
            $deckUser = $deck->getUser();
            $isAdmin = $this->customGenericService->checkIfUserIsAdmin($user);
            if ($isAdmin === FALSE && $deck->isIsPublic() === FALSE && $deckUser->getId() !== $user->getId()) {
                $response["error"] = "Deck not available.";
                return $response;
            }
            $publicValue = $public === 1;
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
     * @param string $jwt
     * @param int $id
     * @param array $parameter
     * @param Card $cardService
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     */
    public function update(string $jwt, int $id, array $parameter, CardService $cardService): array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse()];
        try {
            [
                "error" => $errorCheckUserDeck,
                "errorDebug" => $errorDebugCheckUserDeck,
                "user" => $user,
                "deck" => $deck,
            ] = $this->checkUserAndDeck($jwt, $id);
            if (empty($errorCheckUserDeck) === FALSE) {
                $response["error"] = $errorCheckUserDeck;
                $response["errorDebug"] = $errorDebugCheckUserDeck;
                return $response;
            }
            $deckUser = $deck->getUser();
            $isAdmin = $this->customGenericService->checkIfUserIsAdmin($user);
            if ($isAdmin === FALSE && $deckUser->getId() !== $user->getId()) {
                $response["error"] = "Deck not available.";
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