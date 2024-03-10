<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\PropertyType as PropertyTypeService;

#[Route("/property-type", name: "api_property_type")]
class PropertyTypeController extends CustomAbstractController
{
    #[Route('/all', name: '_get_all', methods: ["GET"])]
    public function getAll(PropertyTypeService $propertyTypeService): JsonResponse
    {
        return $this->genericGetAll($propertyTypeService, "propertyType", "Property Type");
    }
}
