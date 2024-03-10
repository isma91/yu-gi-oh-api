<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\CardAttribute as CardAttributeService;

#[Route("/card-attribute", name: "api_card_attribute")]
class CardAttributeController extends CustomAbstractController
{
    #[Route('/all', name: '_get_all', methods: ["GET"])]
    public function getAll(CardAttributeService $cardAttributeService): JsonResponse
    {
        return $this->genericGetAll($cardAttributeService, "cardAttribute", "Card Attribute");
    }
}
