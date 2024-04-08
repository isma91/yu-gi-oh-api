<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use App\Entity\Set as SetEntity;
use Nelmio\ApiDocBundle\Annotation\Areas;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\Set as SetService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\Requirement\Requirement;

#[OA\Tag(name: "Set")]
#[Route("/set", name: "api_set")]
class SetController extends CustomAbstractController
{
    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "Set Info",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(
                    property: "set",
                    ref: "#/components/schemas/SetInfo",
                ),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when getting Set",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(
                    property: "set",
                    ref: "#/components/schemas/SetInfo",
                ),
            ]
        )
    )]
    #[OA\Parameter(
        name: "id",
        description: "Unique identifier of the Set",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[Security(name: "Bearer")]
    #[Route(
        '/info/{id}',
        name: '_get_info',
        requirements: [
            'id' => Requirement::DIGITS,
        ],
        methods: ["GET"],
    )]
    public function getInfo(
        Request $request,
        SetEntity $setEntity,
        SetService $setService
    ): JsonResponse
    {
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
            "set" => $set
        ] = $setService->getInfo($setEntity);
        $data = ["set" => $set];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("Set info", $data);
    }
}
