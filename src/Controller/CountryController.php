<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use Nelmio\ApiDocBundle\Annotation\Areas;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\Country as CountryService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[OA\Tag(name: "Country")]
#[Route("/country", name: "api_country")]
class CountryController extends CustomAbstractController
{
    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "List of all Country",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(
                    property: "country",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/CountryList")),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when getting all Country",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(
                    property: "country",
                    description: "Sometimes an empty array",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/CountryList")
                ),
            ]
        )
    )]
    #[Security(name: "Bearer")]
    #[Route('/all', name: '_get_all', methods: ["GET"])]
    public function getAll(Request $request, CountryService $countryService): JsonResponse
    {
        return $this->genericGetAll($request, $countryService, "country", "Country");
    }
}
