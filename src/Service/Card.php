<?php

namespace App\Service;

use App\Service\Tool\Card\ORM as CardORMService;
use Exception;

class Card
{
    private CustomGeneric $customGenericService;

    private CardORMService $cardORMService;

    public function __construct(CustomGeneric $customGenericService, CardORMService $cardORMService)
    {
        $this->customGenericService = $customGenericService;
        $this->cardORMService = $cardORMService;
    }

    /**
     * @return CardORMService
     */
    public function getORMService(): CardORMService
    {
        return $this->cardORMService;
    }

    /**
     * @param string $jwt
     * @param string $cardUuid
     * @return array[
     *   "error" => string,
     *   "errorDebug" => string,
     *   "card" => array[mixed]|null,
     * ]
     */
    public function getCardInfo(string $jwt, string $cardUuid): array
    {
        $response = [
            ...$this->customGenericService->getEmptyReturnResponse(),
            "card" => NULL,
        ];
        try {
            $user = $this->customGenericService->customGenericCheckJwt($jwt);
            if ($user === NULL) {
                $response["error"] = "No user found.";
                return $response;
            }
            $card = $this->cardORMService->findByUuid($cardUuid);
            if ($card === NULL) {
                $response["error"] = "Card not found.";
                return $response;
            }
            $isAdmin = $this->customGenericService->checkIfUserIsAdmin($user);
            $cardSerialize = $this->customGenericService->getInfoSerialize([$card], ["card_info"])[0];
            $decksSerialize = $this->customGenericService->getInfoSerialize($card->getDecks()->toArray(), ["card_info"]);
            $deckList = [];
            foreach ($decksSerialize as $deckInfo) {
                [
                    "isPublic" => $isPublic,
                    "user" => $deckUser
                ] = $deckInfo;
                if (empty($deckUser) === TRUE) {
                    continue;
                }
                if ($isAdmin === FALSE && $isPublic === FALSE && $deckUser[0]->getUsername() !== $user->getUsername()) {
                    continue;
                }
                $deckList[] = $deckInfo;
            }
            $cardSerialize["decks"] = $deckList;
            $response["card"] = $cardSerialize;
            $this->customGenericService->addInfoLogFromDebugBacktrace();
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while getting Card info.";
        }
        return $response;
    }

    /**
     * @return array[
     *   "error" => string,
     *   "errorDebug" => string,
     *   "card" => array[mixed]|null,
     * ]
     */
    public function getRandomCardInfo(): array
    {
        $response = [
            ...$this->customGenericService->getEmptyReturnResponse(),
            "card" => NULL,
        ];
        try {
            $card = NULL;
            $count = 0;
            $countMax = 50;
            //try to find a Card or after $countMax attempt
            while ($card === NULL && $count <= $countMax) {
                $card = $this->cardORMService->findRandom();
                $count++;
            }
            if ($card !== NULL) {
                $response["card"] = $this->customGenericService->getInfoSerialize([$card], ["card_random_info"])[0];
            }
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Damn !! Random Card error...";
        }
        return $response;
    }
}