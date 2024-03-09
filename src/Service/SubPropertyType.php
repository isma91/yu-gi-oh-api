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
     * @return array[
     *  "error" => string,
     *  "errorDebug" => string,
     *  "subPropertyType" => array[mixed],
     *  ]
     */
    public function getAll(): array
    {
        return $this->customGenericService->getAllOrInfo(
            $this->subPropertyTypeORMService,
            "subPropertyType",
            ["sub_property_type_list"],
            "Sub Property Type"
        );
    }
}