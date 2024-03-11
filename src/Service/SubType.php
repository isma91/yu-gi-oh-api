<?php

namespace App\Service;

use App\Service\Tool\SubType\ORM as SubTypeORMService;
use Exception;

class SubType
{
    private CustomGeneric $customGenericService;

    private SubTypeORMService $subTypeORMService;

    public function __construct(CustomGeneric $customGenericService, SubTypeORMService $subTypeORMService)
    {
        $this->customGenericService = $customGenericService;
        $this->subTypeORMService = $subTypeORMService;
    }

    /**
     * @return SubTypeORMService
     */
    public function getORMService(): SubTypeORMService
    {
        return $this->subTypeORMService;
    }

    /**
     * @param string $jwt
     * @return array[
     *  "error" => string,
     *  "errorDebug" => string,
     *  "subType" => array[mixed],
     *  ]
     */
    public function getAll(string $jwt): array
    {
        return $this->customGenericService->getAllOrInfo(
            $jwt,
            $this->subTypeORMService,
            "subType",
            ["sub_type_list"],
            "Sub Type"
        );
    }
}