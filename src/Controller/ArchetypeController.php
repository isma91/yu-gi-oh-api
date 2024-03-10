<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\Archetype as ArchetypeService;

#[Route("/archetype", name: "api_archetype")]
class ArchetypeController extends CustomAbstractController
{
    #[Route('/all', name: '_get_all', methods: ["GET"])]
    public function getAll(ArchetypeService $archetypeService): JsonResponse
    {
        return $this->genericGetAll($archetypeService, "archetype", "Archetype");
    }
}
