<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use App\Service\CardPicture as CardPictureService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[OA\Tag(name: "Card Picture")]
#[Route("/card-picture", name: "card_picture_api")]
class CardPictureController extends CustomAbstractController
{
    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "File content of a picture",
    )]
    #[OA\Parameter(
        name: "uuid",
        description: "Uuid of the Card",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "string", format: Requirement::UUID_V7)
    )]
    #[OA\Parameter(
        name: "idYGO",
        description: "Unique identifier of the CardPicture",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Parameter(
        name: "name",
        description: "file name of the picture",
        in: "path",
        required: true
    )]
    #[Route(
        '/display/{uuid}/{idYGO}/{name}',
        name: '_display_card_picture_file',
        requirements: [
            'uuid' => Requirement::UUID_V7,
            'idYGO' => Requirement::DIGITS,
            'name' => Requirement::CATCH_ALL
        ],
        methods: ["GET"])
    ]
    public function displayCardPicture(
        string $uuid,
        int $idYGO,
        string $name,
        Request $request,
        CardPictureService $cardPictureService
    ): BinaryFileResponse
    {
        return new BinaryFileResponse($cardPictureService->getPicture($uuid, $idYGO, $name));
    }
}
