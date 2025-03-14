<?php

namespace App\Service;

use App\Entity\User as UserEntity;
use App\Entity\UserToken as UserTokenEntity;
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
     * @param string $jwt
     * @param string $frontRole
     * @return array
     * @throws JsonException
     */
    private function _getUserInfoLogin(UserEntity $user, string $jwt, string $frontRole): array
    {
        $userSerialize = $this->customGenericService->getInfoSerialize([$user], ["user_login"])[0];
        return array_merge(["jwt" => $jwt, "role" => $frontRole], $userSerialize);
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
            $userAuthInfo = $this->userAuthService->loginAndGetInfo($user);
            $response["user"] = $this->_getUserInfoLogin($user, $userAuthInfo["jwt"], $userAuthInfo["role"]);
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while login.";
        }
        $this->customGenericService->enableSoftDeleteable();
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
            $role = $this->userAuthService->getRoleFrontName($user);
            $response["user"] = $this->_getUserInfoLogin($user, $jwt, $role);
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
            $this->customGenericService->disableSoftDeleteable();
            $users = $this->userORMService->findAll();
            foreach ($users as $userInfo) {
                $userSerialize = $this->customGenericService->getInfoSerialize([$userInfo], ["user_admin_list"])[0];
                $userSerialize["role"] = $this->userAuthService->getRoleFrontName($userInfo);
                $userSerialize["userTokenCount"] = $userInfo->getUserTokens()->count();
                $response["user"][] = $userSerialize;
            }
            $this->customGenericService->enableSoftDeleteable();
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while getting all user info.";
            $this->customGenericService->enableSoftDeleteable();
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
            $this->customGenericService->disableSoftDeleteable();
            $userSerialize = $this->customGenericService->getInfoSerialize([$user], ["user_admin_info"])[0];
            $userSerialize["role"] = $this->userAuthService->getRoleFrontName($user);
            $response["user"] = $userSerialize;
            $this->customGenericService->enableSoftDeleteable();
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while getting user admin info.";
        }
        return $response;
    }

    /**
     * @return array[
     * "error" => string,
     * "errorDebug" => string
     */
    public function revokeToken(UserTokenEntity $userToken):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse()];
        try {
            $this->userAuthService->logout($userToken);
            $this->customGenericService->addInfoLogFromDebugBacktrace();
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while revoking token.";
        }
        return $response;
    }

    /**
     * @param array $parameter
     * @return array[
     * "error" => string,
     * "errorDebug" => string
     */
    public function create(array $parameter):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse()];
        try {
            [
                "username" => $username,
                "password" => $password,
                "confirmPassword" => $confirmPassword
            ] = $parameter;
            $duplicate = $this->userORMService->findByUserIdentifiant($username);
            if ($duplicate !== NULL) {
                $response["error"] = sprintf("Username '%s' already taken.", $username);
                return $response;
            }
            if ($password !== $confirmPassword) {
                $response["error"] = "Please correctly confirm your password.";
                return $response;
            }
            $currentDate = new \DateTime();
            $user = new UserEntity();
            $user->setUsername($username)
                ->setCreatedAt($currentDate)
                ->setUpdatedAt($currentDate);
            $user = $this->userAuthService->editPassword($user, $password);
            $this->userORMService->persist($user);
            $this->userORMService->flush();
            $this->customGenericService->addInfoLogFromDebugBacktrace();
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while creating user.";
        }
        return $response;
    }

    /**
     * @param string $jwt
     * @return array[
     * "error" => string,
     * "errorDebug" => string,
     * "userToken" => array,
     */
    public function getCurrentUserAllToken(string $jwt):array
    {
        $response = [...$this->customGenericService->getEmptyReturnResponse(), "userToken" => []];
        try {
            ["user" => $user] = $this->userAuthService->checkJWT($jwt);
            if ($user === NULL) {
                $response["error"] = "User not found.";
                return $response;
            }
            $userSerialize = $this->customGenericService->getInfoSerialize([$user], ["user_token_info"])[0];
            $userTokens = $user->getUserTokens();
            foreach ($userTokens as $userTokenIndex => $userToken) {
                $userTrackings = $userToken->getUserTrackings();
                $potentialArray = [];
                foreach ($userTrackings as $userTracking) {
                    $userTrackingInfo = $userTracking->getInfo();
                    $potentialIp = NULL;
                    $potentialPreciseIp = NULL;
                    $potentialAddress = NULL;
                    $potentialGeoip = NULL;
                    $potentialArrayToAdd = [];
                    foreach ($userTrackingInfo as $key => $value) {
                        if ($key === "ip" && empty($value) === FALSE) {
                            $potentialIp = $value;
                        }
                        if (empty($value["CITY"]) === FALSE && str_ends_with($key, "_GEOIP") === TRUE) {
                            if (empty($value["ASN"]) === FALSE && empty($value["ASN"]["ip_address"]) === FALSE) {
                                $potentialPreciseIp = $value["ASN"]["ip_address"];
                            }
                            if (empty($value["CITY"]["address"]) === FALSE) {
                                $potentialAddress = $value["CITY"]["address"];
                            }
                            if (empty($value["CITY"]["location"]) === FALSE &&
                                empty($value["CITY"]["location"]["latitude"]) === FALSE &&
                                empty($value["CITY"]["location"]["longitude"]) === FALSE
                            ) {
                                $accuracyRadius = NULL;
                                if (empty($value["CITY"]["location"]["accuracy_radius"]) === FALSE) {
                                    $accuracyRadius = $value["CITY"]["location"]["accuracy_radius"];
                                }
                                $potentialGeoip = [
                                    "latitude" => $value["CITY"]["location"]["latitude"],
                                    "longitude" => $value["CITY"]["location"]["longitude"],
                                    "accuracy_radius" => $accuracyRadius
                                ];
                            }
                        }
                        if ($potentialIp !== NULL) {
                            $potentialArrayToAdd["ip"] = $potentialIp;
                        }
                        if ($potentialPreciseIp !== NULL) {
                            $potentialArrayToAdd["mostPreciseIp"] = $potentialPreciseIp;
                        }
                        if ($potentialGeoip !== NULL) {
                            $potentialArrayToAdd["geoip"] = $potentialGeoip;
                        }
                        if ($potentialAddress !== NULL) {
                            $potentialArrayToAdd["address"] = $potentialAddress;
                        }
                        $potentialArray[] = $potentialArrayToAdd;
                    }
                }
                $truePotentialArray = max($potentialArray);
                foreach (["ip", "mostPreciseIp", "address", "geoip"] as $item) {
                    if (empty($truePotentialArray[$item]) === TRUE) {
                        $truePotentialArray[$item] = NULL;
                    }
                }
                $userSerialize["userTokens"][$userTokenIndex] += $truePotentialArray;
                $response["userToken"] = $userSerialize["userTokens"];
            }
        } catch (Exception $e) {
            $this->customGenericService->addExceptionLog($e);
            $response["errorDebug"] = sprintf('Exception : %s', $e->getMessage());
            $response["error"] = "Error while getting your user token.";
        }
        return $response;
    }
}