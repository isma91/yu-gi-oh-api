<?php

namespace App\Service;

use App\Entity\User as UserEntity;
use App\Service\Tool\User\ORM as UserORMService;
use App\Service\Tool\User\Auth as UserAuthService;
use Exception;

class User
{
    private CustomGeneric $customGenericService;
    private UserORMService $userORMService;
    private UserAuthService $userAuthService;

    public function __construct(
        CustomGeneric $customGenericService,
        UserAuthService $userAuthService,
        UserORMService $userORMService
    )
    {
        $this->customGenericService = $customGenericService;
        $this->userAuthService = $userAuthService;
        $this->userORMService = $userORMService;
    }

    /**
     * @param UserEntity $user
     * @return array
     * @throws Exception
     */
    private function _getUserInfoLogin(UserEntity $user): array
    {
        $userAuthInfo = $this->userAuthService->loginAndGetInfo($user);
        $userSerialize = $this->customGenericService->getInfoSerialize([$user], ["user_login"])[0];
        return array_merge($userAuthInfo, $userSerialize);
    }

    /**
     * @param array $parameter
     * @return array[
     *  "error" => string,
     *  "errorDebug" => string,
     *  "user" => array[mixed],
     *  ]
     */
    public function login(array $parameter): array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse(), "user" => []];
        try {
            [
                "username" => $username,
                "password" => $password
            ] = $parameter;
            $this->customGenericService->disableSoftDeleteable();
            $user = $this->userORMService->findByUserIdentifiant($username);
            if ($user === NULL) {
                $this->customGenericService->enableSoftDeleteable();
                $response["error"] = "Bad username or password.";
                return $response;
            }
            $isPasswordValid = $this->userAuthService->checkUserPasswordValid($user, $password);
            if ($isPasswordValid === FALSE) {
                $this->customGenericService->enableSoftDeleteable();
                $response["error"] = "Bad username or password.";
                return $response;
            }
            if ($user->getDeletedAt() !== NULL) {
                $this->customGenericService->enableSoftDeleteable();
                $response["error"] = "User deleted, please contact the tech team.";
                return $response;
            }
            $response["user"] = $this->_getUserInfoLogin($user);
            $this->customGenericService->enableSoftDeleteable();
        } catch (Exception $e) {
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while login.";
        }
        return $response;
    }

    /**
     * @param string $jwt
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     * "user" => array[mixed],
     * ]
     */
    public function loginFromJwt(string $jwt): array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse(), "user" => []];
        try {
            $user = $this->userAuthService->checkJwt($jwt);
            if ($user === NULL) {
                $response["error"] = "No user found.";
                return $response;
            }
            $response["user"] = $this->_getUserInfoLogin($user);
        } catch (Exception $e) {
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while login refresh.";
        }
        return $response;
    }

    /**
     * @param string $jwt
     * @return array
     */
    public function logout(string $jwt):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse()];
        try {
            $user = $this->userAuthService->checkJwt($jwt);
            if ($user === NULL) {
                $response["error"] = "No user found.";
                return $response;
            }
            $this->userAuthService->logout($user);
        } catch (Exception $e) {
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while logout.";
        }
        return $response;
    }
}