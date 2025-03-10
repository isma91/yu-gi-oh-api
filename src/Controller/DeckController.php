<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use App\Entity\Deck as DeckEntity;
use App\Security\Voter\DeckVoter;
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
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: "Deck")]
#[Route("/deck", name: "api_deck")]
class DeckController extends CustomAbstractController
{
    #[OA\Response(
        response: SymfonyResponse::HTTP_CREATED,
        description: "Deck created successfully",
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
            schema: new OA\Schema(ref: "#/components/schemas/DeckCreateRequest"),
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

    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "Deck Info",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(
                    property: "deck",
                    ref: "#/components/schemas/DeckInfo",
                ),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when getting Deck",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(
                    property: "deck",
                    ref: "#/components/schemas/DeckInfo",
                ),
            ]
        )
    )]
    #[OA\Parameter(
        name: "id",
        description: "Unique identifier of the Deck, must be your Deck or a public one.",
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
    #[IsGranted(DeckVoter::INFO, subject: "deckEntity")]
    public function getInfo(
        Request $request,
        DeckEntity $deckEntity,
        DeckService $deckService
    ): JsonResponse
    {
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
            "deck" => $deck
        ] = $deckService->getInfo($deckEntity);
        $data = ["deck" => $deck];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("Deck info", $data);
    }

    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "Deck deleted",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when deleting Deck",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
            ]
        )
    )]
    #[OA\Parameter(
        name: "id",
        description: "Unique identifier of the Deck, must be your Deck.",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[Security(name: "Bearer")]
    #[Route(
        '/delete/{id}',
        name: '_delete_from_id',
        requirements: [
            'id' => Requirement::DIGITS,
        ],
        methods: ["DELETE"],
    )]
    #[IsGranted(DeckVoter::DELETE, subject: "deckEntity")]
    public function deleteFromId(
        Request $request,
        DeckEntity $deckEntity,
        DeckService $deckService
    ): JsonResponse
    {
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
        ] = $deckService->deleteFromId($deckEntity);
        if ($error !== "") {
            return $this->sendError($error, $errorDebug);
        }
        return $this->sendSuccess("Deck deleted successfully");
    }

    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "Deck info updated",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(
                    property: "deck",
                    ref: "#/components/schemas/DeckInfo",
                ),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when updating Deck",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(
                    property: "deck",
                    ref: "#/components/schemas/DeckInfo",
                ),
            ]
        )
    )]
    #[OA\Parameter(
        name: "id",
        description: "Unique identifier of the Deck, must be your Deck or a public one.",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Parameter(
        name: "public",
        description: "Set public to 0 for private or 1 to be public",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[Security(name: "Bearer")]
    #[Route(
        '/update-public/{id}/{public}',
        name: '_update_public_from_id',
        requirements: [
            'id' => Requirement::DIGITS,
            'public' => "[0-1]",
        ],
        methods: ["PUT"],
    )]
    #[IsGranted(DeckVoter::UPDATE, subject: "deckEntity")]
    public function updatePublicFromId(
        DeckEntity $deckEntity,
        int $public,
        Request $request,
        DeckService $deckService
    ): JsonResponse
    {
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
            "deck" => $deckInfo
        ] = $deckService->updatePublic($deckEntity, $public);
        $data = ["deck" => $deckInfo];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $deckInfo);
        }
        return $this->sendSuccess("Deck successfully updated.", $data);
    }

    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "Deck updated successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when updating Deck",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
            ]
        )
    )]
    #[OA\Parameter(
        name: "id",
        description: "Unique identifier of the Deck, must be your Deck",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\RequestBody(
        request: "DeckUpdateRequest",
        description: "Deck info to update",
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(ref: "#/components/schemas/DeckCreateRequest"),
        )
    )]
    #[Security(name: "Bearer")]
    #[Route(
        '/edit/{id}',
        name: '_edit',
        requirements: [
            'id' => Requirement::DIGITS,
            ],
        methods: ["POST"]
    )]
    #[IsGranted(DeckVoter::UPDATE, subject: "deckEntity")]
    public function edit(
        Request $request,
        DeckEntity $deckEntity,
        DeckService $deckService,
        CardService $cardService
    ): JsonResponse
    {
        $waitedParameter = [
            "name" => "string",
            "isPublic_OPT" => "boolean",
            "artwork_OPT" => "int",
            "deck-card" => "array",
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
        ] = $deckService->update($deckEntity, $parameter, $cardService);
        if ($error !== "") {
            return $this->sendError($error, $errorDebug);
        }
        return $this->sendSuccess("Deck successfully updated");
    }
}
