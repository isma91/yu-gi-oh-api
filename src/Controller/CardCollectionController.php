<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use App\Entity\CardCollection as CardCollectionEntity;
use App\Security\Voter\CardCollectionVoter;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\CardCollection as CardCollectionService;
use App\Service\Card as CardService;
use App\Service\Country as CountryService;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: "Card Collection")]
#[Route("/card-collection", name: "api_card_collection")]
class CardCollectionController extends CustomAbstractController
{
    #[OA\Response(
        response: SymfonyResponse::HTTP_CREATED,
        description: "Card Collection created successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when creating Card Collection",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
            ]
        )
    )]
    #[OA\RequestBody(
        request: "CardCollectionCreateRequest",
        description: "Card Collection info to create",
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(ref: "#/components/schemas/CardCollectionCreateRequest"),
        )
    )]
    #[Security(name: "Bearer")]
    #[Route('/create', name: '_create', methods: ["POST"])]
    public function create(
        Request $request,
        CardCollectionService $cardCollectionService,
        CardService $cardService,
        CountryService $countryService
    ): JsonResponse
    {
        $waitedParameter = [
            "name" => "string",
            "isPublic_OPT" => "boolean",
            "artwork_OPT" => "int",
            "card-collection" => "array",
        ];
        [
            "error" => $error,
            "parameter" => $parameter,
            "jwt" => $jwt
        ] = $this->checkRequestParameter(
            $request,
            $waitedParameter
        );
        if ($error !== "") {
            return $this->sendError($error);
        }
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
        ] = $cardCollectionService->create(
            $jwt,
            $parameter,
            $cardService,
            $countryService
        );
        if ($error !== "") {
            return $this->sendError($error, $errorDebug);
        }
        return $this->sendSuccess(
            "Collection successfully created",
            NULL,
            Response::HTTP_CREATED
        );
    }

    #[OA\Response(
        response: SymfonyResponse::HTTP_CREATED,
        description: "Card Collection info.",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(property: "collection", ref: "#/components/schemas/CardCollectionInfo"),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when getting Card Collection",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(
                    property: "collection",
                    ref: "#/components/schemas/CardCollectionInfo",
                    description: "sometimes empty"
                ),
            ]
        )
    )]
    #[Security(name: "Bearer")]
    #[Route(
        '/info/{id}',
        name: '_get_info',
        requirements: [
            'id' => Requirement::DIGITS,
        ],
        methods: ["GET"]
    )]
    #[IsGranted(CardCollectionVoter::INFO, subject: "cardCollectionEntity")]
    public function getInfo(
        Request $request,
        CardCollectionEntity $cardCollectionEntity,
        CardCollectionService $cardCollectionService
    ): JsonResponse
    {
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
            "collection" => $collection,
        ] = $cardCollectionService->getInfo($cardCollectionEntity);
        $data = ["collection" => $collection];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("Collection info.", $data);
    }

    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "Collection deleted",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when deleting Collection",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
            ]
        )
    )]
    #[OA\Parameter(
        name: "id",
        description: "Unique identifier of the Collection, must be your Collection.",
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
    #[IsGranted(CardCollectionVoter::DELETE, subject: "cardCollection")]
    public function deleteFromId(
        Request $request,
        CardCollectionEntity $cardCollectionEntity,
        CardCollectionService $cardCollectionService
    ): JsonResponse
    {
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
        ] = $cardCollectionService->deleteFromId($cardCollectionEntity);
        if ($error !== "") {
            return $this->sendError($error, $errorDebug);
        }
        return $this->sendSuccess("Collection deleted successfully");
    }

    #[OA\Response(
        response: SymfonyResponse::HTTP_CREATED,
        description: "Card Collection info updated.",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(property: "collection", ref: "#/components/schemas/CardCollectionInfo"),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when getting Card Collection",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(
                    property: "collection",
                    ref: "#/components/schemas/CardCollectionInfo",
                    description: "sometimes empty"
                ),
            ]
        )
    )]
    #[OA\Parameter(
        name: "id",
        description: "Unique identifier of the Collection, must be your Collection or a public one.",
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
    #[IsGranted(CardCollectionVoter::UPDATE, subject: "cardCollectionEntity")]
    public function updatePublicFromId(
        int $public,
        CardCollectionEntity $cardCollectionEntity,
        Request $request,
        CardCollectionService $cardCollectionService
    ): JsonResponse
    {
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
            "collection" => $collection,
        ] = $cardCollectionService->updatePublic($cardCollectionEntity, $public);
        $data = ["collection" => $collection];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("Collection updated successfully.", $data);
    }

    #[OA\Response(
        response: SymfonyResponse::HTTP_CREATED,
        description: "Card Collection updated successfully.",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when updated Card Collection",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
            ]
        )
    )]
    #[OA\Parameter(
        name: "id",
        description: "Unique identifier of the Collection, must be your Collection.",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\RequestBody(
        request: "CardCollectionUpdateRequest",
        description: "Card Collection info to update",
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(ref: "#/components/schemas/CardCollectionCreateRequest"),
        )
    )]
    #[Security(name: "Bearer")]
    #[Route(
        '/edit/{id}',
        name: '_edit_from_id',
        requirements: [
            'id' => Requirement::DIGITS,
        ],
        methods: ["POST"],
    )]
    #[IsGranted(CardCollectionVoter::UPDATE, subject: "cardCollectionEntity")]
    public function edit(
        Request $request,
        CardCollectionEntity $cardCollectionEntity,
        CardCollectionService $cardCollectionService,
        CardService $cardService,
        CountryService $countryService
    ): JsonResponse
    {
        $waitedParameter = [
            "name" => "string",
            "isPublic_OPT" => "boolean",
            "artwork_OPT" => "int",
            "card-collection" => "array",
        ];
        [
            "error" => $error,
            "parameter" => $parameter,
        ] = $this->checkRequestParameter(
            $request,
            $waitedParameter,
            FALSE
        );
        if ($error !== "") {
            return $this->sendError($error);
        }
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
        ] = $cardCollectionService->update(
            $cardCollectionEntity,
            $parameter,
            $cardService,
            $countryService
        );
        if ($error !== "") {
            return $this->sendError($error, $errorDebug);
        }
        return $this->sendSuccess("Collection successfully updated");
    }
}
