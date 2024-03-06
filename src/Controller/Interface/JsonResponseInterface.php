<?php

namespace App\Controller\Interface;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

interface JsonResponseInterface
{
    /**
     * Send a predefined Json Response
     * @param string $errorSuccess
     * @param string $message
     * @param $data
     * @param int $httpCode
     * @return JsonResponse
     */
    public function sendJsonResponse(
        string $errorSuccess,
        string $message,
               $data = NULL,
        int $httpCode = Response::HTTP_OK
    ): JsonResponse;

    /**
     * Alias to SendJsonResponse with success
     * @param $message
     * @param null $data
     * @param int $httpCode
     * @return JsonResponse
     */
    public function sendSuccess($message, $data = NULL, int $httpCode = Response::HTTP_OK): JsonResponse;

    /**
     * Alias to SendJsonResponse with error
     * @param string $message
     * @param null $data
     * @param int $httpCode
     * @return JsonResponse
     */
    public function sendError(
        string $message,
               $data = NULL,
        int $httpCode = Response::HTTP_BAD_REQUEST
    ): JsonResponse;
}