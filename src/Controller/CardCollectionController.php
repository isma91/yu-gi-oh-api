<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use Nelmio\ApiDocBundle\Annotation\Areas;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\CardCollection as CardCollectionService;
use App\Service\Card as CardService;
use App\Service\Country as CountryService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\Requirement\Requirement;

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

    #[Security(name: "Bearer")]
    #[Route(
        '/info/{id}',
        name: '_get_info',
        requirements: [
            'id' => Requirement::DIGITS,
        ],
        methods: ["GET"]
    )]
    public function getInfo(
        int $id,
        Request $request,
        CardCollectionService $cardCollectionService
    ): JsonResponse
    {
        $jwt = $this->getJwt($request);
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
            "collection" => $collection,
        ] = $cardCollectionService->getInfo($jwt, $id);
        $data = ["collection" => $collection];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("Collection info.", $data);
    }
}
