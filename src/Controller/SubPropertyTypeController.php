<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\SubPropertyType as SubPropertyTypeService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[OA\Tag(name: "Sub Property Type")]
#[Route("/sub-property-type", name: "api_sub_property_type")]
class SubPropertyTypeController extends CustomAbstractController
{
    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "List of all SubPropertyType with his SubProperty children",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(
                    property: "subPropertyType",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SubPropertyTypeWithSubPropertyList")),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when getting all SubPropertyType with his SubProperty children",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(
                    property: "subPropertyType",
                    description: "Sometimes an empty array",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SubPropertyTypeWithSubPropertyList")
                ),
            ]
        )
    )]
    #[Security(name: "Bearer")]
    #[Route('/all', name: '_get_all', methods: ["GET"])]
    public function getAll(Request $request, SubPropertyTypeService $subPropertyTypeService): JsonResponse
    {
        return $this->genericGetAll($request, $subPropertyTypeService, "subPropertyType", "Sub Property Type");
    }
}
