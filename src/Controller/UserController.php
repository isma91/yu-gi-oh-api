<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\User as UserService;

#[Route('/user', name: 'api_user')]
class UserController extends CustomAbstractController
{
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
