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
     * @return PropertyTypeORMService
     */
    public function getORMService(): PropertyTypeORMService
    {
        return $this->propertyTypeORMService;
    }

    /**
     * @param string $jwt
     * @return array[
     *  "error" => string,
     *  "errorDebug" => string,
     *  "propertyType" => array[mixed],
     *  ]
     */
    public function getAll(string $jwt): array
    {
        return $this->customGenericService->getAllOrInfo(
            $jwt,
            $this->propertyTypeORMService,
            "propertyType",
            ["property_type_list"],
            "Property Type"
        );
    }
}