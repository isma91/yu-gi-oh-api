<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use Nelmio\ApiDocBundle\Annotation\Areas;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\Deck as DeckService;
use App\Service\Card as CardService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Deck")]
#[Route("/deck", name: "api_deck")]
class DeckController extends CustomAbstractController
{
    #[OA\Response(
        response: SymfonyResponse::HTTP_CREATED,
        description: "",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when creating Deck",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
            ]
        )
    )]
    #[OA\RequestBody(
        request: "DeckCreateRequest",
        description: "Deck info to create",
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(
                        property: "name",
                        description: "Name of the Deck.",
                        type: "string"
                    ),
                    new OA\Property(
                        property: "isPublic",
                        description: "If the Deck is going to be seen from other.",
                        type: "boolean"
                    ),
                    new OA\Property(
                        property: "artwork",
                        description: "Id of the card Artwork to use",
                        type: "integer",
                        nullable: true
                    ),
                    new OA\Property(
                        property: "deck-card",
                        schema: "#/components/schemas/DeckCardContent",
                        description: "See DeckCardContent Schema below",
                    ),
                ]
            ),
        )
    )]
    #[Security(name: "Bearer")]
    #[Route('/create', name: '_create', methods: ["POST"])]
    public function create(Request $request, DeckService $deckService, CardService $cardService): JsonResponse
    {
        $waitedParameter = [
            "name" => "string",
            "isPublic_OPT" => "boolean",
            "artwork_OPT" => "int",
            "deck-card" => "array",
        ];
        [
            "error" => $error,
            "parameter" => $parameter,
            "jwt" => $jwt
        ] = $this->checkRequestParameter($request, $waitedParameter);
        if ($error !== "") {
            return $this->sendError($error);
        }
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
        ] = $deckService->create($jwt, $parameter, $cardService);
        if ($error !== "") {
            return $this->sendError($error, $errorDebug);
        }
        return $this->sendSuccess("Deck successfully created", NULL, Response::HTTP_CREATED);
    }

    #[OA\RequestBody(
        request: "SearchDeckUserRequest",
        description: "Filter to find specific Deck from current User.",
        required: false,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(
                        property: "name",
                        description: "Part of name or description of the Deck",
                        type: "string"
                    ),
                    new OA\Property(
                        property: "offset",
                        description: "Page number if you want to access to the next {limit} number Deck",
                        type: "integer"
                    ),
                    new OA\Property(
                        property: "limit",
                        description: "Number of Deck result we send back, if the total is more than {limit}",
                        type: "integer",
                        maximum: 100,
                        minimum: 1,
                    ),
                ]
            )
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "List of all current User's Deck",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(
                    property: "deck",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/DeckUserList")),
                new OA\Property(
                    property: "deckAllResultCount",
                    description: "Result number of all Deck from filter, for pagination purpose",
                    type: "integer",
                ),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when getting all Deck from current User",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(
                    property: "deck",
                    description: "Sometimes an empty array",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/DeckUserList")
                ),
                new OA\Property(
                    property: "deckAllResultCount",
                    description: "Result number of all Deck from filter, for pagination purpose",
                    type: "integer",
                ),
            ]
        )
    )]
    #[Security(name: "Bearer")]
    #[Route('/list', name: '_list_from_user', methods: ["GET"])]
    public function listFromUser(Request $request, DeckService $deckService): JsonResponse
    {
        $waitedParameter = [
            "name_OPT" => "string",
            "offset_OPT" => "int",
            "limit_OPT" => "int",
        ];
        [
            "error" => $error,
            "parameter" => $parameter,
            "jwt" => $jwt
        ] = $this->checkRequestParameter($request, $waitedParameter);
        if ($error !== "") {
            return $this->sendError($error);
        }
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
            "deck" => $deck,
            "deckAllResultCount" => $deckAllResultCount
        ] = $deckService->listFromUser($jwt, $parameter);
        $data = ["deck" => $deck, "deckAllResultCount" => $deckAllResultCount];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("Deck list", $data);
    }
}
