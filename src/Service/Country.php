<?php

namespace App\Service;

use App\Service\Tool\Country\ORM as CountryORMService;
use Exception;

class Country
{
    private CustomGeneric $customGenericService;

    private CountryORMService $countryORMService;

    public function __construct(CustomGeneric $customGenericService, CountryORMService $archetypeORMService)
    {
        $this->customGenericService = $customGenericService;
        $this->countryORMService = $archetypeORMService;
    }

    /**
     * @return CountryORMService
     */
    public function getORMService(): CountryORMService
    {
        return $this->countryORMService;
    }

    /**
     * @param string $jwt
     * @return array[
     *  "error" => string,
     *  "errorDebug" => string,
     *  "archetype" => array[mixed],
     *  ]
     */
    public function getAll(string $jwt): array
    {
        return $this->customGenericService->getAllOrInfo(
            $jwt,
            $this->countryORMService,
            "country",
            ["country_list"],
            "Country"
        );
    }
}