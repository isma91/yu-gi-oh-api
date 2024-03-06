<?php

namespace App\Controller;

use App\Controller\Interface\JsonResponseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CustomJsonResponse implements JsonResponseInterface
{
    public function sendJsonResponse(
        string $errorSuccess,
        string $message,
        $data = NULL,
        int $httpCode = Response::HTTP_OK
    ): JsonResponse
    {
        return new JsonResponse([$errorSuccess => $message, "data" => $data], $httpCode);
    }

    public function sendError(
        string $message,
        $data = NULL,
        int $httpCode = Response::HTTP_BAD_REQUEST
    ): JsonResponse
    {
        return $this->sendJsonResponse("error", $message, $data, $httpCode);
    }

    public function sendSuccess($message, $data = NULL, int $httpCode = Response::HTTP_OK): JsonResponse
    {
        return $this->sendJsonResponse("success", $message, $data, $httpCode);
    }
}