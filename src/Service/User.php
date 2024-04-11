<?php

namespace App\Service;

use App\Entity\User as UserEntity;
use App\Service\Tool\User\ORM as UserORMService;
use App\Service\Tool\User\Auth as UserAuthService;
use Exception;
use JsonException;

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
     * @throws JsonException
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
            $this->customGenericService->addExceptionLog($e);
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
            [
                "user" => $user,
                "userToken" => $userToken
            ] = $this->userAuthService->checkJwt($jwt, TRUE);
            if ($user === NULL) {
                $response["error"] = "No user found.";
                return $response;
            }
            $response["user"] = $this->_getUserInfoLogin($user);
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
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
            [
                "user" => $user,
                "userToken" => $userToken
            ] = $this->userAuthService->checkJwt($jwt, TRUE);
            if ($user === NULL) {
                $response["error"] = "No user found.";
                return $response;
            }
            $this->userAuthService->logout($userToken);
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while logout.";
        }
        return $response;
    }

    /**
     * @param string $jwt
     * @param array $parameter
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     */
    public function editPassword(string $jwt, array $parameter):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse()];
        try {
            ["user" => $user] = $this->userAuthService->checkJwt($jwt);
            if ($user === NULL) {
                $response["error"] = "No user found.";
                return $response;
            }
            [
                "currentPassword" => $currentPassword,
                "password" => $newPassword,
                "confirmPassword" => $confirmPassword,
            ] = $parameter;
            if ($newPassword !== $confirmPassword) {
                $response["error"] = "You must confirm your new password, they are not the same !!";
                return $response;
            }
            $isPasswordValid = $this->userAuthService->checkUserPasswordValid($user, $currentPassword);
            if ($isPasswordValid === FALSE) {
                $response["error"] = "Bad password";
                return $response;
            }
            $user = $this->userAuthService->editPassword($user, $newPassword);
            $this->userORMService->persist($user);
            $this->userORMService->flush();
            $this->customGenericService->loggerService
                ->setLevel(Logger::INFO)
                ->setIsCron(FALSE)
                ->addLog(sprintf("User %s change password", $user->getUsername()));
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while updating your password.";
        }
        return $response;
    }

    /**
     * @param string $jwt
     * @param string $newUsername
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     */
    public function editUsername(string $jwt, string $newUsername):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse()];
        try {
            if (strlen($newUsername) < 3) {
                $response["error"] = "Your new Username must be at least 3 character long !!";
                return $response;
            }
            ["user" => $user] = $this->userAuthService->checkJwt($jwt);
            if ($user === NULL) {
                $response["error"] = "No user found.";
                return $response;
            }
            $oldUsername = $user->getUsername();
            $userWithNewUsername = $this->userORMService->findByUserIdentifiant($newUsername);
            if ($userWithNewUsername !== NULL) {
                $response["error"] = "Username already Taken !!";
                return $response;
            }
            $user->setUsername($newUsername);
            $this->userORMService->persist($user);
            $this->userORMService->flush();
            $this->customGenericService->loggerService
                ->setLevel(Logger::INFO)
                ->setIsCron(FALSE)
                ->addLog(
                    sprintf(
                        "User change username, old => %s, new => %s",
                        $oldUsername, $newUsername
                    )
                );
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while updating your username.";
        }
        return $response;
    }

    /**
     * @param string $jwt
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     * "user" => array[mixed]
     */
    public function getBasicInfo(string $jwt):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse(), "user" => []];
        try {
            ["user" => $user] = $this->userAuthService->checkJwt($jwt);
            if ($user === NULL) {
                $response["error"] = "No user found.";
                return $response;
            }
            $response["user"] = $this->customGenericService->getInfoSerialize([$user], ["user_basic_info"])[0];
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while getting info.";
        }
        return $response;
    }

    /**
     * @param string $jwt
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     * "user" => array[mixed]
     */
    public function getAll(string $jwt):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse(), "user" => []];
        try {
            ["user" => $user] = $this->userAuthService->checkJwt($jwt);
            if ($user === NULL) {
                $response["error"] = "No user found.";
                return $response;
            }
            $users = $this->userORMService->findAll();
            foreach ($users as $userInfo) {
                $userSerialize = $this->customGenericService->getInfoSerialize([$userInfo], ["user_admin_list"])[0];
                $userSerialize["role"] = $this->userAuthService->getRoleFrontName($userInfo);
                $userSerialize["userTokenCount"] = $userInfo->getUserTokens()->count();
                $response["user"][] = $userSerialize;
            }
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while getting all user info.";
        }
        return $response;
    }

    /**
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     * "user" => array[mixed]
     */
    public function getUserAdminInfo(UserEntity $user):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse(), "user" => []];
        try {
            $userSerialize = $this->customGenericService->getInfoSerialize([$user], ["user_admin_info"])[0];
            $userSerialize["role"] = $this->userAuthService->getRoleFrontName($user);
            $response["user"] = $userSerialize;
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while getting user admin info.";
        }
        return $response;
    }
}