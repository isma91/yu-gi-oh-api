<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\SubType as SubTypeService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[OA\Tag(name: "Sub Type")]
#[Route("/sub-type", name: "api_sub_type")]
class SubTypeController extends CustomAbstractController
{
    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "List of all SubType",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(
                    property: "subType",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SubTypeList")),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when getting all SubType",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(
                    property: "subType",
                    description: "Sometimes an empty array",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SubTypeList")
                ),
            ]
        )
    )]
    #[Security(name: "Bearer")]
    #[Route('/all', name: '_get_all', methods: ["GET"])]
    public function getAll(Request $request, SubTypeService $subTypeService): JsonResponse
    {
        return $this->genericGetAll($request, $subTypeService, "subType", "Sub Type");
    }
}
