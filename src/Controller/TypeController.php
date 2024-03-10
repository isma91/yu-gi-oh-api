<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\Type as TypeService;

#[Route("/type", name: "api_type")]
class TypeController extends CustomAbstractController
{
    #[Route('/all', name: '_get_all', methods: ["GET"])]
    public function getAll(TypeService $typeService): JsonResponse
    {
        return $this->genericGetAll($typeService, "type", "Type");
    }
}
