<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use App\Service\CardPicture as CardPictureService;

#[Route("/card-picture", name: "card_picture_api")]
class CardPictureController extends CustomAbstractController
{
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
