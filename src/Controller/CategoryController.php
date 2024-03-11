<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\Category as CategoryService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[OA\Tag(name: "Category")]
#[Route("/category", name: "api_category")]
class CategoryController extends CustomAbstractController
{
    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "List of all Category with his SubCategory children",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(
                    property: "category",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/CategoryWithSubCategoryList")),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when getting all Category with his SubCategory children",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(
                    property: "category",
                    description: "Sometimes an empty array",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/CategoryWithSubCategoryList")
                ),
            ]
        )
    )]
    #[Security(name: "Bearer")]
    #[Route('/all', name: '_get_all', methods: ["GET"])]
    public function getAll(Request $request, CategoryService $categoryService): JsonResponse
    {
        return $this->genericGetAll($request, $categoryService, "category", "Category");
    }
}
