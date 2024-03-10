<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\Category as CategoryService;

#[Route("/category", name: "api_category")]
class CategoryController extends CustomAbstractController
{
    #[Route('/all', name: '_get_all', methods: ["GET"])]
    public function getAll(CategoryService $categoryService): JsonResponse
    {
        return $this->genericGetAll($categoryService, "category", "Category");
    }
}
