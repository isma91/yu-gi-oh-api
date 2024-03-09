<?php

namespace App\Controller\Abstract;

use App\Controller\Interface\CheckParameterInterface;
use App\Controller\Interface\JsonResponseInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class CustomAbstractController extends AbstractController
{
    protected JsonResponseInterface $jsonResponse;
    protected CheckParameterInterface $checkParameter;

    public function __construct(JsonResponseInterface $jsonResponse, CheckParameterInterface $checkParameter)
    {
        $this->jsonResponse = $jsonResponse;
        $this->checkParameter = $checkParameter;
    }

    /**
     * @param Request $request
     * @param array $waitedParameter
     * @param bool $addJwt
     * @return array[
     * "error" => string,
     * "parameters" => array,
     * "jwt" => undefined|string
     * ]
     */
    public function checkRequestParameter(
        Request $request,
        array   $waitedParameter = [],
        bool    $addJwt = TRUE,
    ): array
    {
        $parameter = $request->request->all();
        $error = "";
        $newParameter = [];
        if (empty($waitedParameter) === FALSE) {
            [
                "error" => $error,
                "parameter" => $newParameter
            ] = $this->checkParameter->checkParameter($parameter, $waitedParameter);
        }
        $response = ["error" => $error, "parameter" => $newParameter];
        if ($addJwt === TRUE) {
            $response["jwt"] = $this->getJwt($request);
        }
        return $response;
    }

    /**
     * @param Request $request
     * @return string
     */
    public function getJwt(Request $request): string
    {
        return str_replace("Bearer ", '', $request->headers->get('Authorization'));
    }

    public function sendError(
        string $message,
        string $messageDebug = "",
               $data = NULL,
        int $httpCode = Response::HTTP_BAD_REQUEST
    ): JsonResponse
    {
        $resMessage = $message;
        if (empty($messageDebug) === FALSE && $this->getParameter("APP_ENV") !== "prod") {
            $resMessage = $messageDebug;
        }
        return $this->jsonResponse->sendError($resMessage, $data, $httpCode);
    }

    public function sendSuccess($message, $data = NULL, int $httpCode = Response::HTTP_OK): JsonResponse
    {
        return $this->jsonResponse->sendSuccess($message, $data, $httpCode);
    }
}