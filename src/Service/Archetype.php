<?php

namespace App\Service;

use App\Service\Tool\Archetype\ORM as ArchetypeORMService;
use Exception;

class Archetype
{
    private CustomGeneric $customGenericService;

    private ArchetypeORMService $archetypeORMService;

    public function __construct(CustomGeneric $customGenericService, ArchetypeORMService $archetypeORMService)
    {
        $this->customGenericService = $customGenericService;
        $this->archetypeORMService = $archetypeORMService;
    }

    /**
     * @return ArchetypeORMService
     */
    public function getORMService(): ArchetypeORMService
    {
        return $this->archetypeORMService;
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
            $this->archetypeORMService,
            "archetype",
            ["archetype_list"],
            "Archetype"
        );
    }
}