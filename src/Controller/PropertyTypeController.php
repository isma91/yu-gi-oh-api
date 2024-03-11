<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\PropertyType as PropertyTypeService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[OA\Tag(name: "Property Type")]
#[Route("/property-type", name: "api_property_type")]
class PropertyTypeController extends CustomAbstractController
{
    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "List of all PropertyType with his Property children",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(
                    property: "propertyType",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/PropertyTypeWithPropertyList")),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when getting all PropertyType with his Property children",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(
                    property: "propertyType",
                    description: "Sometimes an empty array",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/PropertyTypeWithPropertyList")
                ),
            ]
        )
    )]
    #[Security(name: "Bearer")]
    #[Route('/all', name: '_get_all', methods: ["GET"])]
    public function getAll(Request $request, PropertyTypeService $propertyTypeService): JsonResponse
    {
        return $this->genericGetAll($request, $propertyTypeService, "propertyType", "Property Type");
    }
}
