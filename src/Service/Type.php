<?php

namespace App\Service;

use App\Service\Tool\Type\ORM as TypeORMService;
use Exception;

class Type
{
    private CustomGeneric $customGenericService;

    private TypeORMService $typeORMService;

    public function __construct(CustomGeneric $customGenericService, TypeORMService $typeORMService)
    {
        $this->customGenericService = $customGenericService;
        $this->typeORMService = $typeORMService;
    }

    /**
     * @return TypeORMService
     */
    public function getORMService(): TypeORMService
    {
        return $this->typeORMService;
    }

    /**
     * @param string $jwt
     * @return array[
     *  "error" => string,
     *  "errorDebug" => string,
     *  "type" => array[mixed],
     *  ]
     */
    public function getAll(string $jwt): array
    {
        return $this->customGenericService->getAllOrInfo(
            $jwt,
            $this->typeORMService,
            "type",
            ["type_list"],
            "Type"
        );
    }
}