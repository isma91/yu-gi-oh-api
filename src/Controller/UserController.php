<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use App\Service\User as UserService;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[OA\Tag(name: "User")]
#[Route('/user', name: 'api_user')]
class UserController extends CustomAbstractController
{
    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "User info",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(
                    property: "userInfo",
                    ref: "#/components/schemas/UserLogin",
                ),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when login and getting User info",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(
                    property: "userInfo",
                    description: "Sometimes an empty array",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/UserLogin")
                ),
            ]
        )
    )]
    #[OA\RequestBody(
        request: "UserLoginInfoRequest",
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                required: ["username", "password"],
                properties: [
                    new OA\Property(property: "username", type: "string"),
                    new OA\Property(property: "password", type: "string"),
                ]
            )
        )
    )]
    #[Route('/login', name: '_login', methods: ['POST'])]
    public function login(Request $request, UserService $userService): JsonResponse
    {
        $waitedParameter = ["username" => "string", "password" => "password"];
        [
            "error" => $error,
            "parameter" => $parameter
        ] = $this->checkRequestParameter($request, $waitedParameter, FALSE);
        if ($error !== "") {
            return $this->sendError($error);
        }
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
            "user" => $userInfo
        ] = $userService->login($parameter);
        $data = ["userInfo" => $userInfo];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("Login successfully", $data);
    }

    /**
     * @param Request $request
     * @param UserService $userService
     * @return JsonResponse
     */
    #[Route("/refresh-login", name: "_refresh_login", methods: ["GET"])]
    public function refreshLogin(Request $request, UserService $userService): JsonResponse
    {
        $jwt = $this->getJwt($request);
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
            "user" => $userInfo
        ] = $userService->loginFromJwt($jwt);
        $data = ["userInfo" => $userInfo];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("User login successfully refreshed", $data);
    }

    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "Logout successful message",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(property: "data", type: null),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when logout",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(property: "data", type: null),
            ]
        )
    )]
    #[Security(name: "Bearer")]
    #[Route("/logout", name: "_logout", methods: ["GET"])]
    public function logout(Request $request, UserService $userService):JsonResponse
    {
        $jwt = $this->getJwt($request);
        [
            "error" => $error,
            "errorDebug" => $errorDebug
        ] = $userService->logout($jwt);
        if ($error !== "") {
            return $this->sendError($error, $errorDebug);
        }
        return $this->sendSuccess("User successfully logout.");
    }
}
