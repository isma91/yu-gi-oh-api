<?php

namespace App\Service;

use App\Service\Tool\CardAttribute\ORM as CardAttributeORMService;
use Exception;

class CardAttribute
{
    private CustomGeneric $customGenericService;

    private CardAttributeORMService $cardAttributeORMService;

    public function __construct(CustomGeneric $customGenericService, CardAttributeORMService $cardAttributeORMService)
    {
        $this->customGenericService = $customGenericService;
        $this->cardAttributeORMService = $cardAttributeORMService;
    }

    /**
     * @return CardAttributeORMService
     */
    public function getORMService(): CardAttributeORMService
    {
        return $this->cardAttributeORMService;
    }

    /**
     * @param string $jwt
     * @return array[
     *  "error" => string,
     *  "errorDebug" => string,
     *  "cardAttribute" => array[mixed],
     *  ]
     */
    public function getAll(string $jwt): array
    {
        return $this->customGenericService->getAllOrInfo(
            $jwt,
            $this->cardAttributeORMService,
            "cardAttribute",
            ["card_attribute_list"],
            "Card Attribute"
        );
    }
}