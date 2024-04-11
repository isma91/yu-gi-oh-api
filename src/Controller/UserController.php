<?php

namespace App\Controller;

use App\Controller\Abstract\CustomAbstractController;
use App\Security\Voter\UserVoter;
use App\Service\User as UserService;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User as UserEntity;

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

    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "User's password updated successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when updating User's password",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
            ]
        )
    )]
    #[OA\RequestBody(
        request: "UserEditPasswordRequest",
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                required: ["currentPassword", "password", "confirmPassword"],
                properties: [
                    new OA\Property(
                        property: "currentPassword",
                        description: "The User's current password",
                        type: "string"
                    ),
                    new OA\Property(
                        property: "password",
                        description: "The new password",
                        type: "string"
                    ),
                    new OA\Property(
                        property: "confirmPassword",
                        description: "The same new password, to avoid bad password edit",
                        type: "string"
                    ),
                ]
            )
        )
    )]
    #[Security(name: "Bearer")]
    #[Route("/edit-password", name: "_edit_password", methods: ["POST"])]
    public function editPassword(Request $request, UserService $userService):JsonResponse
    {
        $waitedParameter = [
            "currentPassword" => "password",
            "password" => "password",
            "confirmPassword" => "password",
        ];
        [
            "error" => $error,
            "parameter" => $parameter,
            "jwt" => $jwt,
        ] = $this->checkRequestParameter($request, $waitedParameter);
        if ($error !== "") {
            return $this->sendError($error);
        }
        [
            "error" => $error,
            "errorDebug" => $errorDebug
        ] = $userService->editPassword($jwt, $parameter);
        if ($error !== "") {
            return $this->sendError($error, $errorDebug);
        }
        return $this->sendSuccess("Your password updated successfully.");
    }

    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "Username updated successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when updating your username.",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
            ]
        )
    )]
    #[OA\Parameter(
        name: "username",
        description: "New username of the User, must be at least 3 character long",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "string")
    )]
    #[Security(name: "Bearer")]
    #[Route(
        "/edit-username/{username}",
        name: "_edit_username",
        requirements: ["username" => Requirement::CATCH_ALL],
        methods: ["PUT"]
    )]
    public function editUsername(string $username, Request $request, UserService $userService):JsonResponse
    {
        $jwt = $this->getJwt($request);
        [
            "error" => $error,
            "errorDebug" => $errorDebug
        ] = $userService->editUsername($jwt, $username);
        if ($error !== "") {
            return $this->sendError($error, $errorDebug);
        }
        return $this->sendSuccess("Your username is updated successfully.");
    }

    #[OA\Response(
        response: SymfonyResponse::HTTP_OK,
        description: "User get basic info",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "string"),
                new OA\Property(property: "user", ref: "#/components/schemas/UserBasicInfo"),
            ]
        )
    )]
    #[OA\Response(
        response: SymfonyResponse::HTTP_BAD_REQUEST,
        description: "Error when getting your info.",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string"),
                new OA\Property(property: "user", ref: "#/components/schemas/UserBasicInfo"),
            ]
        )
    )]
    #[Security(name: "Bearer")]
    #[Route(
        "/basic-info",
        name: "_get_basic_info",
        requirements: ["username" => Requirement::CATCH_ALL],
        methods: ["GET"]
    )]
    public function getBasicInfo(Request $request, UserService $userService):JsonResponse
    {
        $jwt = $this->getJwt($request);
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
            "user" => $user
        ] = $userService->getBasicInfo($jwt);
        $data = ["user" => $user];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("User basic info.", $data);
    }

    #[Route(
        "/all",
        name: "_get_all",
        methods: ["GET"]
    )]
    public function getAll(Request $request, UserService $userService):JsonResponse
    {
        $jwt = $this->getJwt($request);
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
            "user" => $user
        ] = $userService->getAll($jwt);
        $data = ["user" => $user];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("All user info.", $data);
    }

    #[Route(
        "/admin-info/{id}",
        name: "_get_user_admin_info",
        methods: ["GET"]
    )]
    #[IsGranted(UserVoter::USER_ADMIN_INFO, subject: "userEntity")]
    public function getAdminInfo(
        Request $request,
        UserEntity $userEntity,
        UserService $userService
    ):JsonResponse
    {
        [
            "error" => $error,
            "errorDebug" => $errorDebug,
            "user" => $user
        ] = $userService->getUserAdminInfo($userEntity);
        $data = ["user" => $user];
        if ($error !== "") {
            return $this->sendError($error, $errorDebug, $data);
        }
        return $this->sendSuccess("User info.", $data);
    }
}
