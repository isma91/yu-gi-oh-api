<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\SubType as SubTypeService;

#[Route("/sub-type", name: "api_sub_type")]
class SubTypeController extends CustomAbstractController
{
    #[Route('/all', name: '_get_all', methods: ["GET"])]
    public function getAll(SubTypeService $subTypeService): JsonResponse
    {
        return $this->genericGetAll($subTypeService, "subType", "Sub Type");
    }
}
