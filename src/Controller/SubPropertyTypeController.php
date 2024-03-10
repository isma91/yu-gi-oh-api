<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\SubPropertyType as SubPropertyTypeService;

#[Route("/sub-property-type", name: "api_sub_property_type")]
class SubPropertyTypeController extends CustomAbstractController
{
    #[Route('/all', name: '_get_all', methods: ["GET"])]
    public function getAll(SubPropertyTypeService $subPropertyTypeService): JsonResponse
    {
        return $this->genericGetAll($subPropertyTypeService, "subPropertyType", "Sub Property Type");
    }
}
