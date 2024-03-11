<?php

namespace App\Service;

use App\Service\Tool\SubPropertyType\ORM as SubPropertyTypeORMService;
use Exception;

class SubPropertyType
{
    private CustomGeneric $customGenericService;

    private SubPropertyTypeORMService $subPropertyTypeORMService;

    public function __construct(CustomGeneric $customGenericService, SubPropertyTypeORMService $subPropertyTypeORMService)
    {
        $this->customGenericService = $customGenericService;
        $this->subPropertyTypeORMService = $subPropertyTypeORMService;
    }

    /**
     * @return SubPropertyTypeORMService
     */
    public function getORMService(): SubPropertyTypeORMService
    {
        return $this->subPropertyTypeORMService;
    }

    /**
     * @param string $jwt
     * @return array[
     *  "error" => string,
     *  "errorDebug" => string,
     *  "subPropertyType" => array[mixed],
     *  ]
     */
    public function getAll(string $jwt): array
    {
        return $this->customGenericService->getAllOrInfo(
            $jwt,
            $this->subPropertyTypeORMService,
            "subPropertyType",
            ["sub_property_type_list"],
            "Sub Property Type"
        );
    }
}