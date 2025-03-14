<?php

namespace App\Service;

use App\Service\Tool\Category\ORM as CategoryORMService;
use Exception;

class Category
{
    private CustomGeneric $customGenericService;

    private CategoryORMService $categoryORMService;

    public function __construct(CustomGeneric $customGenericService, CategoryORMService $categoryORMService)
    {
        $this->customGenericService = $customGenericService;
        $this->categoryORMService = $categoryORMService;
    }

    /**
     * @return CategoryORMService
     */
    public function getORMService(): CategoryORMService
    {
        return $this->categoryORMService;
    }

    /**
     * @param string $jwt
     * @return array[
     *  "error" => string,
     *  "errorDebug" => string,
     *  "category" => array[mixed],
     *  ]
     */
    public function getAll(string $jwt): array
    {
        return $this->customGenericService->getAllOrInfo(
            $jwt,
            $this->categoryORMService,
            "category",
            ["category_list"],
            "Category"
        );
    }
}