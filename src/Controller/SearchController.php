<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use App\Service\Search as SearchService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/search', name: 'api_search')]
class SearchController extends CustomAbstractController
{
    #[Route('/card', name: '_card', methods: ['POST'])]
    public function search(Request $request, SearchService $searchService): JsonResponse
    {
        $waitedParameter = [
            "name_OPT" => "string",
            "offset_OPT" => "int",
            "limit_OPT" => "int",
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
            "card" => $cardResultArray,
            "cardAllResultCount" => $cardAllResultCount
        ] = $searchService->card($parameter);
        $data = ["card" => $cardResultArray, "cardAllResultCount" => $cardAllResultCount];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("Card search result.", $data);
    }
}
