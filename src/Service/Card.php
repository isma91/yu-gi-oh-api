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
            $response["card"] = $this->customGenericService->getInfoSerialize([$card], ["card_info"])[0];
        } catch (Exception $e) {
            dd($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while getting Card info.";
        }
        return $response;
    }
}