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
     * @return array[
     *  "error" => string,
     *  "errorDebug" => string,
     *  "cardAttribute" => array[mixed],
     *  ]
     */
    public function getAll(): array
    {
        return $this->customGenericService->getAllOrInfo(
            $this->cardAttributeORMService,
            "cardAttribute",
            ["card_attribute_list"],
            "Card Attribute"
        );
    }
}