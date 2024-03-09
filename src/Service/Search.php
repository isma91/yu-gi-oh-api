<?php

namespace App\Service;

use App\Service\Tool\Card\ORM as CardORMService;
use Exception;

class Search
{
    private CustomGeneric $customGenericService;
    private CardORMService $cardORMService;

    private int $offset = 0;
    private int $limit = 30;

    public function __construct(
        CustomGeneric $customGenericService,
        CardORMService $cardORMService
    )
    {
        $this->customGenericService = $customGenericService;
        $this->cardORMService = $cardORMService;
    }

    /**
     * @param array $parameter
     * @return array[
     *  "error" => string,
     *  "errorDebug" => string,
     *  "card" => array[mixed],
     *  "cardAllResultCount" => int
     *  ]
     */
    public function card(
        array $parameter
    ): array
    {
        $response = [
            ...$this->customGenericService->getEmptyReturnResponse(),
            "card" => [],
            "cardAllResultCount" => 0
        ];
        try {
            [
                "name" => $name,
                "offset" => $offset,
                "limit" => $limit,
            ] = $parameter;
            $cardORMSearchService = $this->cardORMService->getORMSearch();
            $filter = [];
            if (empty($name) === FALSE) {
                $filter["slugName"] = $this->customGenericService->slugify($name);
            }
            if ($offset > 0) {
                $cardORMSearchService->offset = $offset;
            }
            if ($limit > 0) {
                $cardORMSearchService->limit = $limit;
            }
            $cardORMSearchServiceOffset = $cardORMSearchService->offset;
            $cardORMSearchServiceLimit = $cardORMSearchService->limit;
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
}