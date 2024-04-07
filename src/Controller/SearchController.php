<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use App\Service\Card as CardService;
use App\Service\Deck as DeckService;
use App\Service\Set as SetService;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\Search as SearchService;
use App\Service\Archetype as ArchetypeService;
use App\Service\CardAttribute as CardAttributeService;
use App\Service\Category as CategoryService;
use App\Service\PropertyType as PropertyTypeService;
use App\Service\SubPropertyType as SubPropertyTypeService;
use App\Service\SubType as SubTypeService;
use App\Service\Type as TypeService;
use App\Service\CardCollection as CardCollectionService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[OA\Tag(name: "Search")]
#[Route('/search', name: 'api_search')]
class SearchController extends CustomAbstractController
{
    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "List of all Card from filter",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(
                    property: "cardAllResultCount",
                    description: "Result number of all Card from filter, for pagination purpose",
                    type: "integer"
                ),
                new OA\Property(
                    property: "card",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SearchCardList")),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when getting all Card from filter",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(
                    property: "cardAllResultCount",
                    description: "Result number of all Card from filter, for pagination purpose",
                    type: "integer"
                ),
                new OA\Property(
                    property: "card",
                    description: "Sometimes an empty array",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SearchCardList")
                ),
            ]
        )
    )]
    #[OA\RequestBody(
        request: "SearchCardRequest",
        description: "Filter to find specific Card.
         Please note that each field is taken as AND but if we have multiple value for the same field we put as OR.
        For example: We search every card with 'dragon' in it BUT we want all Card Monster with Race 'Dragon' OR 'Warrior' OR 'Elf';
        We need to put these 3 ids of Race in the filter AND the name value to 'dragon'.",
        required: false,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(
                        property: "name",
                        description: "Part of name or description of the Card",
                        type: "string"
                    ),
                    new OA\Property(
                        property: "offset",
                        description: "Page number if you want to access to the next {limit} number card",
                        type: "integer"
                    ),
                    new OA\Property(
                        property: "limit",
                        description: "Number of Card result we send back, if the total is more than {limit}",
                        type: "integer",
                        maximum: 100,
                        minimum: 1,
                    ),
                    new OA\Property(
                        property: "archetype",
                        description: "Ids of Archetype separated by a comma",
                        type: "string",
                        example: "1,2,5"
                    ),
                    new OA\Property(
                        property: "cardAttribute",
                        description: "Ids of Attribute separated by a comma",
                        type: "string",
                        example: "1,2,5"
                    ),
                    new OA\Property(
                        property: "category",
                        description: "Id of Category",
                        type: "integer",
                    ),
                    new OA\Property(
                        property: "subCategory",
                        description: "Id of SubCategory",
                        type: "integer",
                    ),
                    new OA\Property(
                        property: "propertyType",
                        description: "Id of PropertyType ( monster card level type ).",
                        type: "integer",
                    ),
                    new OA\Property(
                        property: "property",
                        description: "2 element separated with comma: the minimum level value and the maximum level value",
                        type: "string",
                        example: "2,8"
                    ),
                    new OA\Property(
                        property: "subPropertyType",
                        description: "Id of SubPropertyType",
                        type: "integer",
                    ),
                    new OA\Property(
                        property: "subProperty",
                        description: "Ids of SubProperty separated by a comma",
                        type: "string",
                        example: "5,6"
                    ),
                    new OA\Property(
                        property: "subType",
                        description: "Ids of SubType separated by a comma",
                        type: "string",
                        example: "5,6"
                    ),
                    new OA\Property(
                        property: "type",
                        description: "Ids of Race separated by a comma",
                        type: "string",
                        example: "5,6"
                    ),
                    new OA\Property(
                        property: "isPendulum",
                        description: "if the card is a Pendulum monster, leave null if it's not a monster",
                        type: "string",
                        example: "true",
                        nullable: true
                    ),
                    new OA\Property(
                        property: "isEffect",
                        description: "if the card is an effect monster, leave null if it's not a monster",
                        type: "string",
                        example: "true",
                        nullable: true
                    ),
                ]
            )
        )
    )]
    #[Security(name: "Bearer")]
    #[Route('/card', name: '_card', methods: ['POST'])]
    public function card(
        Request $request,
        SearchService $searchService,
        CardService $cardService,
        ArchetypeService $archetypeService,
        CardAttributeService $cardAttributeService,
        CategoryService $categoryService,
        PropertyTypeService $propertyTypeService,
        SubPropertyTypeService $subPropertyTypeService,
        SubTypeService $subTypeService,
        TypeService $typeService
    ): JsonResponse
    {
        $waitedParameter = [
            "name_OPT" => "string",
            "offset_OPT" => "int",
            "limit_OPT" => "int",
            "archetype_OPT" => "explode_int",
            "cardAttribute_OPT" => "explode_int",
            "category_OPT" => "int",
            "subCategory_OPT" => "int",
            "propertyType_OPT" => "int",
            "property_OPT" => "explode_int",
            "subPropertyType_OPT" => "int",
            "subProperty_OPT" => "explode_int",
            "subType_OPT" => "explode_int",
            "type_OPT" => "explode_int",
            "isPendulum_OPT" => "string",
            "isEffect_OPT" => "string",
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
            "card" => $cardResultArray,
            "cardAllResultCount" => $cardAllResultCount
        ] = $searchService->card(
            $jwt,
            $parameter,
            $cardService,
            $archetypeService,
            $cardAttributeService,
            $categoryService,
            $propertyTypeService,
            $subPropertyTypeService,
            $subTypeService,
            $typeService
        );
        $data = ["card" => $cardResultArray, "cardAllResultCount" => $cardAllResultCount];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("Card search result.", $data);
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
                        description: "Part of name of the Deck",
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
    #[Route('/deck-current-user', name: '_deck_current_user', methods: ["POST"])]
    public function deckCurrentUser(
        Request $request,
        SearchService $searchService,
        DeckService $deckService
    ): JsonResponse
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
        ] = $searchService->deckCurrentUser($jwt, $parameter, $deckService);
        $data = ["deck" => $deck, "deckAllResultCount" => $deckAllResultCount];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("Deck list", $data);
    }

    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "List of all Set from filter",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(
                    property: "setAllResultCount",
                    description: "Result number of all set from filter, for pagination purpose",
                    type: "integer"
                ),
                new OA\Property(
                    property: "set",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SetSearchList")),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when getting all Card from filter",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(
                    property: "setAllResultCount",
                    description: "Result number of all Set from filter, for pagination purpose",
                    type: "integer"
                ),
                new OA\Property(
                    property: "set",
                    description: "Sometimes an empty array",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SetSearchList")
                ),
            ]
        )
    )]
    #[OA\RequestBody(
        request: "SearchSetRequest",
        description: "Filter to find specific Set.",
        required: false,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(
                        property: "name",
                        description: "Part of name of the Set",
                        type: "string"
                    ),
                    new OA\Property(
                        property: "offset",
                        description: "Page number if you want to access to the next {limit} number Set",
                        type: "integer"
                    ),
                    new OA\Property(
                        property: "limit",
                        description: "Number of Set result we send back, if the total is more than {limit}",
                        type: "integer",
                        maximum: 100,
                        minimum: 1,
                    ),
                    new OA\Property(
                        property: "code",
                        description: "Set code",
                        type: "string",
                    ),
                    new OA\Property(
                        property: "yearBegin",
                        description: "Put a limit of Set who's release date is after or start at the year set",
                        type: "integer",
                        maximum: 2100,
                        minimum: 1900
                    ),
                    new OA\Property(
                        property: "yearEnd",
                        description: "Put a limit of Set who's release date is not after the year set",
                        type: "integer",
                        maximum: 2100,
                        minimum: 1900
                    ),
                ]
            )
        )
    )]
    #[Security(name: "Bearer")]
    #[Route('/set', name: '_set', methods: ["POST"])]
    public function set(
        Request $request,
        SearchService $searchService,
        SetService $setService
    ): JsonResponse
    {
        $waitedParameter = [
            "name_OPT" => "string",
            "code_OPT" => "string",
            "yearBegin_OPT" => "int",
            "yearEnd_OPT" => "int",
            "limit_OPT" => "int",
            "offset_OPT" => "int",
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
            "set" => $set,
            "setAllResultCount" => $setAllResultCount
        ] = $searchService->set($jwt, $parameter, $setService);
        $data = ["set" => $set, "setAllResultCount" => $setAllResultCount];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("Set list", $data);
    }

    #[OA\RequestBody(
        request: "SearchCollectionUserRequest",
        description: "Filter to find specific Collection from current User.",
        required: false,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(
                        property: "name",
                        description: "Part of name of the Collection",
                        type: "string"
                    ),
                    new OA\Property(
                        property: "offset",
                        description: "Page number if you want to access to the next {limit} number Collection",
                        type: "integer"
                    ),
                    new OA\Property(
                        property: "limit",
                        description: "Number of Collection result we send back, if the total is more than {limit}",
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
        description: "List of all current User's Collection",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(
                    property: "collection",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/CardCollectionUserList")),
                new OA\Property(
                    property: "collectionAllResultCount",
                    description: "Result number of all Deck from filter, for pagination purpose",
                    type: "integer",
                ),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when getting Collection from current User",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(
                    property: "collection",
                    description: "Sometimes an empty array",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/CardCollectionUserList")
                ),
                new OA\Property(
                    property: "collectionAllResultCount",
                    description: "Result number of all Collection from filter, for pagination purpose",
                    type: "integer",
                ),
            ]
        )
    )]
    #[Security(name: "Bearer")]
    #[Route('/collection-current-user', name: '_collection_current_user', methods: ["POST"])]
    public function collectionCurrentUser(
        Request $request,
        SearchService $searchService,
        CardCollectionService $cardCollectionService
    ): JsonResponse
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
            "collection" => $collection,
            "collectionAllResultCount" => $collectionAllResultCount
        ] = $searchService->cardCollectionCurrentUser($jwt, $parameter, $cardCollectionService);
        $data = ["collection" => $collection, "collectionAllResultCount" => $collectionAllResultCount];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("Collection list", $data);
    }
}
