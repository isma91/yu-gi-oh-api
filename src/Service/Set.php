<?php

namespace App\Service;

use App\Service\Tool\Set\ORM as SetORMService;
use Exception;

class Set
{
    private CustomGeneric $customGenericService;

    private SetORMService $setORMService;

    public function __construct(CustomGeneric $customGenericService, SetORMService $setORMService)
    {
        $this->customGenericService = $customGenericService;
        $this->setORMService = $setORMService;
    }

    /**
     * @return SetORMService
     */
    public function getORMService(): SetORMService
    {
        return $this->setORMService;
    }
}