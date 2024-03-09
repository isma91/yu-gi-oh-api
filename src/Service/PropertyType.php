<?php

namespace App\Service;

use App\Service\Tool\PropertyType\ORM as PropertyTypeORMService;
use Exception;

class PropertyType
{
    private CustomGeneric $customGenericService;

    private PropertyTypeORMService $propertyTypeORMService;

    public function __construct(CustomGeneric $customGenericService, PropertyTypeORMService $propertyTypeORMService)
    {
        $this->customGenericService = $customGenericService;
        $this->propertyTypeORMService = $propertyTypeORMService;
    }

    /**
     * @return array[
     *  "error" => string,
     *  "errorDebug" => string,
     *  "propertyType" => array[mixed],
     *  ]
     */
    public function getAll(): array
    {
        return $this->customGenericService->getAllOrInfo(
            $this->propertyTypeORMService,
            "propertyType",
            ["property_type_list"],
            "Property Type"
        );
    }
}