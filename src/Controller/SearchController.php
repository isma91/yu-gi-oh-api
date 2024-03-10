<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\Search as SearchService;
use App\Service\Archetype as ArchetypeService;
use App\Service\CardAttribute as CardAttributeService;
use App\Service\Category as CategoryService;
use App\Service\PropertyType as PropertyTypeService;
use App\Service\SubPropertyType as SubPropertyTypeService;
use App\Service\SubType as SubTypeService;
use App\Service\Type as TypeService;

#[Route('/search', name: 'api_search')]
class SearchController extends CustomAbstractController
{
    #[Route('/card', name: '_card', methods: ['POST'])]
    public function search(
        Request $request,
        SearchService $searchService,
        ArchetypeService $archetypeService,
        CardAttributeService $cardAttributeService,
        CategoryService $categoryService,
        PropertyTypeService $propertyTypeService,
        SubPropertyTypeService $subPropertyTypeService,
        SubTypeService $subTypeService,
        TypeService $typeService
    ): JsonResponse
    {
        $waitedParameter = [
            "name_OPT" => "string",
            "offset_OPT" => "int",
            "limit_OPT" => "int",
            "archetype_OPT" => "explode_int",
            "cardAttribute_OPT" => "explode_int",
            "category_OPT" => "int",
            "subCategory_OPT" => "int",
            "propertyType_OPT" => "int",
            "property_OPT" => "explode_int",
            "subPropertyType_OPT" => "int",
            "subProperty_OPT" => "explode_int",
            "subType_OPT" => "explode_int",
            "type_OPT" => "explode_int",
            "isPendulum_OPT" => "string",
            "isEffect_OPT" => "string",
        ];
        [
            "error" => $error,
            "parameter" => $parameter
        ] = $this->checkRequestParameter($request, $waitedParameter, FALSE);
        if ($error !== "") {
            return $this->sendError($error);
        }
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
            "card" => $cardResultArray,
            "cardAllResultCount" => $cardAllResultCount
        ] = $searchService->card(
            $parameter,
            $archetypeService,
            $cardAttributeService,
            $categoryService,
            $propertyTypeService,
            $subPropertyTypeService,
            $subTypeService,
            $typeService
        );
        $data = ["card" => $cardResultArray, "cardAllResultCount" => $cardAllResultCount];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("Card search result.", $data);
    }
}
