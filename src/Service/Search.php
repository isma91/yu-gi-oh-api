<?php

namespace App\Service;

use App\Service\Archetype as ArchetypeService;
use App\Service\Card as CardService;
use App\Service\CardAttribute as CardAttributeService;
use App\Service\Category as CategoryService;
use App\Service\PropertyType as PropertyTypeService;
use App\Service\SubPropertyType as SubPropertyTypeService;
use App\Service\SubType as SubTypeService;
use App\Service\Type as TypeService;
use App\Service\Deck as DeckService;
use Exception;

class Search
{
    private CustomGeneric $customGenericService;
    public function __construct(CustomGeneric $customGenericService)
    {
        $this->customGenericService = $customGenericService;
    }

    /**
     * @param array $filter
     * @param string $filterFieldName
     * @param array|null $entityIdArray
     * @param object $entityService
     * @return array
     */
    protected function fulfillFilterForCardFromEntityIdArray(
        array $filter,
        string $filterFieldName,
        ?array $entityIdArray,
        object $entityService
    ): array
    {
        if (empty($entityIdArray) === FALSE) {
            $entityORMService = $entityService->getORMService();
            foreach ($entityIdArray as $entityId) {
                $entity = $entityORMService->findById($entityId);
                if ($entity !== NULL) {
                    $filter[$filterFieldName][] = $entity;
                }
            }
        }
        return $filter;
    }

    /**
     * @param array $filter
     * @param string $filterFieldName
     * @param int|null $entityId
     * @param object $entityService
     * @return array
     */
    protected function fulfillFilterForCardFromEntityId(
        array $filter,
        string $filterFieldName,
        ?int $entityId,
        object $entityService
    ): array
    {
        if ($entityId !== NULL) {
            $entityORMService = $entityService->getORMService();
            $entity = $entityORMService->findById($entityId);
            if ($entity !== NULL) {
                $filter[$filterFieldName] = $entity;
            }
        }
        return $filter;
    }

    /**
     * @param array $parameter
     * @param Archetype $archetypeService
     * @param CardAttribute $cardAttributeService
     * @param Category $categoryService
     * @param PropertyType $propertyTypeService
     * @param SubPropertyType $subPropertyTypeService
     * @param SubType $subTypeService
     * @param Type $typeService
     * @return array
     */
    public function createFilterForCard(
        array $parameter,
        ArchetypeService $archetypeService,
        CardAttributeService $cardAttributeService,
        CategoryService $categoryService,
        PropertyTypeService $propertyTypeService,
        SubPropertyTypeService $subPropertyTypeService,
        SubTypeService $subTypeService,
        TypeService $typeService
    ): array
    {
        [
            "name" => $name,
            "archetype" => $archetypeIdArray,
            "cardAttribute" => $cardAttributeIdArray,
            "category" => $categoryId,
            "subCategory" => $subCategoryId,
            "propertyType" => $propertyTypeId,
            "property" => $propertyRange,
            "subPropertyType" => $subPropertyTypeId,
            "subProperty" => $subPropertyIdArray,
            "subType" => $subTypeIdArray,
            "type" => $typeIdArray,
            "isPendulum" => $isPendulumValueString,
            "isEffect" => $isEffectMonsterValueString,
        ] = $parameter;
        $filter = [];
        if (empty($isPendulumValueString) === FALSE && $isPendulumValueString !== "null") {
            $filter["isPendulum"] = $isPendulumValueString === "true";
        }
        if (empty($isEffectMonsterValueString) === FALSE && $isEffectMonsterValueString !== "null") {
            $filter["isEffect"] = $isEffectMonsterValueString === "true";
        }
        if (empty($name) === FALSE) {
            $filter["slugName"] = $this->customGenericService->slugify($name);
        }
        $filter = $this->fulfillFilterForCardFromEntityIdArray(
            $filter,
            "archetype",
            $archetypeIdArray,
            $archetypeService
        );
        $filter = $this->fulfillFilterForCardFromEntityIdArray(
            $filter,
            "attribute",
            $cardAttributeIdArray,
            $cardAttributeService
        );
        $filter = $this->fulfillFilterForCardFromEntityId(
            $filter,
            "category",
            $categoryId,
            $categoryService
        );
        if (isset($filter["category"]) === TRUE && $subCategoryId !== NULL) {
            $filterCategoryEntity = $filter["category"];
            $categorySubCategoryArray = $filterCategoryEntity->getSubCategories();
            foreach ($categorySubCategoryArray as $subCategory) {
                if ($subCategory->getId() === $subCategoryId) {
                    $filter["subCategory"] = $subCategory;
                    break;
                }
            }
        }
        $filter = $this->fulfillFilterForCardFromEntityId(
            $filter,
            "propertyType",
            $propertyTypeId,
            $propertyTypeService
        );
        if (isset($filter["propertyType"]) === TRUE && empty($propertyRange) === FALSE && count($propertyRange) === 2) {
            $filterPropertyType = $filter["propertyType"];
            unset($filter["propertyType"]);
            $propertyTypePropertyArray = $filterPropertyType->getProperties();
            sort($propertyRange);
            $propertyRangeArray = range($propertyRange[0], $propertyRange[1]);
            foreach ($propertyTypePropertyArray as $property) {
                $propertyNumber = (int)$property->getSLugName();
                if (in_array($propertyNumber, $propertyRangeArray, TRUE) === TRUE) {
                    $filter["property"][] = $property;
                }
            }
        }
        $filter = $this->fulfillFilterForCardFromEntityId(
            $filter,
            "subPropertyType",
            $subPropertyTypeId,
            $subPropertyTypeService
        );
        if (isset($filter["subPropertyType"]) === TRUE) {
            $filterSubPropertyType = $filter["subPropertyType"];
            $subPropertyTypeSubPropertyArray = $filterSubPropertyType->getSubProperties()->toArray();
            unset($filter["subPropertyType"]);
            if (empty($subPropertyIdArray) === TRUE) {
                $filter["subProperty"] = $subPropertyTypeSubPropertyArray;
            } else {
                foreach ($subPropertyTypeSubPropertyArray as $subProperty) {
                    if (in_array($subProperty->getId(), $subPropertyIdArray, TRUE) === TRUE) {
                        $filter["subProperty"][] = $subProperty;
                    }
                }
            }
        }
        $filter = $this->fulfillFilterForCardFromEntityIdArray(
            $filter,
            "subType",
            $subTypeIdArray,
            $subTypeService
        );
        return $this->fulfillFilterForCardFromEntityIdArray(
            $filter,
            "type",
            $typeIdArray,
            $typeService
        );
    }

    /**
     * @param string $jwt
     * @param array $parameter
     * @param Card $cardService
     * @param Archetype $archetypeService
     * @param CardAttribute $cardAttributeService
     * @param Category $categoryService
     * @param PropertyType $propertyTypeService
     * @param SubPropertyType $subPropertyTypeService
     * @param SubType $subTypeService
     * @param Type $typeService
     * @return array[
     *  "error" => string,
     *  "errorDebug" => string,
     *  "card" => array[mixed],
     *  "cardAllResultCount" => int
     *  ]
     */
    public function card(
        string $jwt,
        array $parameter,
        CardService $cardService,
        ArchetypeService $archetypeService,
        CardAttributeService $cardAttributeService,
        CategoryService $categoryService,
        PropertyTypeService $propertyTypeService,
        SubPropertyTypeService $subPropertyTypeService,
        SubTypeService $subTypeService,
        TypeService $typeService
    ): array
    {
        $response = [
            ...$this->customGenericService->getEmptyReturnResponse(),
            "card" => [],
            "cardAllResultCount" => 0
        ];
        try {
            [
                "offset" => $offset,
                "limit" => $limit,
            ] = $parameter;
            $user = $this->customGenericService->customGenericCheckJwt($jwt);
            if ($user === NULL) {
                $response["error"] = "No user found.";
                return $response;
            }
            $cardORMSearchService = $cardService->getORMService()->getORMSearch();
            if ($offset > 0) {
                $cardORMSearchService->offset = $offset;
            }
            if ($limit > 0) {
                $cardORMSearchService->limit = $limit;
            }
            $filter = $this->createFilterForCard(
                $parameter,
                $archetypeService,
                $cardAttributeService,
                $categoryService,
                $propertyTypeService,
                $subPropertyTypeService,
                $subTypeService,
                $typeService
            );
            $cardResultArray = $cardORMSearchService->findFromSearchFilter($filter);
            $newCardArray = [];
            foreach ($cardResultArray as $card) {
                $cardSerialize = $this->customGenericService->getInfoSerialize([$card], ["search_card"])[0];
                $cardSerializePicture = [];
                $cardSerializePictures = $cardSerialize["pictures"];
                if (empty($cardSerializePictures) === FALSE) {
                    $cardSerializePicture = $cardSerializePictures[0];
                }
                unset($cardSerialize["pictures"]);
                $cardSerialize["picture"] = $cardSerializePicture;
                $newCardArray[] = $cardSerialize;
            }
            $cardAllResultCount = $cardORMSearchService->countFromSearchFilter($filter);
            $response["card"] = $newCardArray;
            $response["cardAllResultCount"] = $cardAllResultCount;
        } catch (Exception $e) {
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while search Card.";
        }
        return $response;
    }

    /**
     * @param string $jwt
     * @param array $parameter
     * @param Deck $deckService
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     * "deck" => array[mixed],
     * "deckAllResultCount" => int
     */
    public function deckCurrentUser(string $jwt, array $parameter, DeckService $deckService): array
    {
        $response = [
            ...$this->customGenericService->getEmptyReturnResponse(),
            "deck" => [],
            "deckAllResultCount" => 0
        ];
        try {
            $user = $this->customGenericService->customGenericCheckJwt($jwt);
            if ($user === NULL) {
                $response["error"] = "No user found.";
                return $response;
            }
            [
                "offset" => $offset,
                "limit" => $limit,
                "name" => $name,
            ] = $parameter;
            $filter  = [
                "user" => $user
            ];
            $deckORMSearch = $deckService->getORMService()->getORMSearch();
            if (empty($offset) === FALSE) {
                $deckORMSearch->offset = $offset;
            }
            if (empty($limit) === FALSE) {
                $deckORMSearch->limit = $limit;
            }
            if (empty($name) === FALSE) {
                $filter["slugName"] = $this->customGenericService->slugify($name);
            }
            $deck = $deckORMSearch->findFromSearchFilter($filter);
            $response["deck"] = $this->customGenericService->getInfoSerialize($deck, ["deck_user_list"]);
            $response["deckAllResultCount"] = $deckORMSearch->countFromSearchFilter($filter);
        } catch (Exception $e) {
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while listing your Decks.";
        }
        return $response;
    }
}