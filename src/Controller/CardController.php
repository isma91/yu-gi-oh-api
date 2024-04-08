<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use App\Entity\Card as CardEntity;
use App\Security\Voter\CardVoter;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\Card as CardService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    #[Security(name: "Bearer")]
    #[Route(
        '/info/{uuid}',
        name: '_get_info_from_uuid',
        requirements: [
            'uuid' => Requirement::UUID_V7,
        ],
        methods: ["GET"]
    )]
    #[IsGranted(CardVoter::VIEW, subject: "cardEntity")]
    public function getInfoFromUuid(
        Request $request,
        #[MapEntity(mapping: ["uuid" => "uuid"])]
        CardEntity $cardEntity,
        CardService $cardService
    ): JsonResponse
    {
        $jwt = $this->getJwt($request);
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
            "card" => $cardInfo,
        ] = $cardService->getCardInfo($jwt, $cardEntity);
        $data = ["card" => $cardInfo];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("Card info.", $data);
    }

    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "Get Random Card info",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(
                    property: "card",
                    ref: "#/components/schemas/CardRandomInfo",
                )
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when getting random Card info",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(
                    property: "card",
                    ref: "#/components/schemas/CardRandomInfo",
                    description: "Card info, can be NULL",
                    nullable: true
                )
            ]
        )
    )]
    #[Route('/random', name: '_get_random', methods: ["GET"])]
    public function getRandom(CardService $cardService): JsonResponse
    {
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
            "card" => $cardInfo,
        ] = $cardService->getRandomCardInfo();
        $data = ["card" => $cardInfo];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("Random Card Go! Go! Go!", $data);
    }
}
