<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\Card as CardService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\Requirement\Requirement;

#[OA\Tag(name: "Card")]
#[Route("/card", name: "api_card")]
class CardController extends CustomAbstractController
{
    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "Card info",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(
                    property: "card",
                    ref: "#/components/schemas/CardInfo",
                )
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when getting Card info from uuid",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(
                    property: "card",
                    ref: "#/components/schemas/CardInfo",
                    description: "Card info, can be NULL",
                    nullable: true
                )
            ]
        )
    )]
    #[OA\Parameter(
        name: "uuid",
        description: "Uuid of the Card",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "string", format: Requirement::UUID_V7)
    )]
    #[Route(
        '/info/{uuid}',
        name: '_get_info_from_uuid',
        requirements: [
            'uuid' => Requirement::UUID_V7,
        ],
        methods: ["GET"]
    )]
    public function getInfoFromUuid(
        Request $request,
        string $uuid,
        CardService $cardService
    ): JsonResponse
    {
        $jwt = $this->getJwt($request);
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
            "card" => $cardInfo,
        ] = $cardService->getCardInfo($jwt, $uuid);
        $data = ["card" => $cardInfo];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("Card info.", $data);
    }
}
