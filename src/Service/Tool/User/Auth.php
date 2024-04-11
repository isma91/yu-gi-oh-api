<?php

namespace App\Service\Tool\User;

use App\Entity\User as UserEntity;
use App\Entity\UserToken as UserTokenEntity;
use App\Service\Tool\User\ORM as UserORMService;
use App\Service\Tool\UserToken\ORM as UserTokenORMService;
use App\Service\Maxmind as MaxmindService;
use DateTime;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use JsonException;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

class Auth
{
    private ParameterBagInterface $param;
    private UserPasswordHasherInterface $userPasswordHasher;
    private UserORMService $userORMService;
    private UserTokenORMService $userTokenORMService;
    private MaxmindService $maxmindService;

    public function __construct(
        ParameterBagInterface $param,
        UserPasswordHasherInterface $userPasswordHasher,
        UserORMService $userORMService,
        UserTokenORMService $userTokenORMService,
        MaxmindService $maxmindService
    )
    {
        $this->param = $param;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->userORMService = $userORMService;
        $this->userTokenORMService = $userTokenORMService;
        $this->maxmindService = $maxmindService;
    }

    /**
     * @param UserEntity $user
     * @param string $password
     * @return bool
     */
    public function checkUserPasswordValid(UserEntity $user, string $password): bool
    {
        return $this->userPasswordHasher->isPasswordValid($user, $password);
    }

    /**
     * Create an array with some user info from server and with the help of Maxmind
     * @return array
     */
    private function _createUserTokenInfo(): array
    {
        $ip1 = $_SERVER['REMOTE_ADDR'] ?? "";
        $ip2 = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? "";
        $ip3 = $_SERVER['HTTP_REMOTE_IP'] ?? "";
        if (($ip1 === $ip2) && ($ip2 === $ip3)) {
            $ip = $ip1;
        } else {
            $ip = sprintf("%s-%s-%s", $ip1, $ip2, $ip3);
        }
        $infoKey = [
            'REMOTE_ADDR',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_REMOTE_IP',
            'HTTP_USER_AGENT',
            'HTTP_ACCEPT_LANGUAGE',
            'HTTP_ACCEPT_ENCODING',
            'HTTP_REFERER',
            'HTTP_X_FORWARDED_PROTO',
        ];
        $info = [
            "ip" => $ip
        ];
        foreach ($infoKey as $key) {
            $value = "";
            if (empty($_SERVER[$key]) === FALSE)  {
                $value = $_SERVER[$key];
            }
            $info[$key] = $value;
        }
        foreach (["REMOTE_ADDR", "HTTP_X_FORWARDED_FOR", "HTTP_REMOTE_IP"] as $item) {
            $newItem = $item . "_GEOIP";
            $maxmindResultArray = $this->maxmindService->findAll($info[$item]);
            foreach ($maxmindResultArray as $key => $value) {
                $info[$newItem][$newItem . "_" . $key] = $value;
            }
        }
        return $info;
    }

    /**
     * Create fingerprint to see if a user is the same
     * @param array $userTokenInfo
     * @return string
     * @throws JsonException
     */
    private function _createFingerprint(array $userTokenInfo): string
    {
        $infoFingerprint = [
            "ip" => $userTokenInfo["ip"],
            "HTTP_USER_AGENT" => $userTokenInfo["HTTP_USER_AGENT"],
            "HTTP_ACCEPT_LANGUAGE" => $userTokenInfo['HTTP_ACCEPT_LANGUAGE'],
            "HTTP_ACCEPT_ENCODING" => $userTokenInfo['HTTP_ACCEPT_ENCODING'],
        ];
        return hash('sha256', json_encode($infoFingerprint, JSON_THROW_ON_ERROR));
    }

    /**
     * Create a UserToken entity
     * @param UserEntity $user
     * @return UserTokenEntity
     * @throws JsonException
     */
    private function _generateUserToken(UserEntity $user): UserTokenEntity
    {
        $userIdentifier = $user->getUserIdentifier();
        $userTokenEntity = new UserTokenEntity();
        $token = md5(uniqid($userIdentifier, TRUE));
        $userTokenInfo = $this->_createUserTokenInfo();
        $fingerprint = $this->_createFingerprint($userTokenInfo);
        $expDatetime = new DateTime();
        $expDatetime->modify("+ 1 month");
        return $userTokenEntity->setToken($token)
            ->setInfo($userTokenInfo)
            ->setFingerprint($fingerprint)
            ->setUuid(Uuid::v7())
            ->setExpiratedAt($expDatetime)
            ->setUser($user)
            ->setCreatedAt(new DateTime())
            ->setUpdatedAt(new DateTime());
    }

    /**
     * @return array["algo" => string, "secret" => string]
     */
    private function _getJWTParam(): array
    {
        return [
            "algo" => $this->param->get("JWT_ALGO"),
            "secret" => $this->param->get("JWT_SECRET"),
        ];
    }

    /**
     * Create a JWT with date who's going to be active and an expiration date
     * @param UserTokenEntity $userTokenEntity
     * @return string
     */
    private function _generateJWT(UserTokenEntity $userTokenEntity): string
    {
        $user = $userTokenEntity->getUser();
        $userTokenCreatedAt = $userTokenEntity->getCreatedAt();
        $userTokenExpiratedAt = $userTokenEntity->getExpiratedAt();
        if ($user === NULL) {
            throw new RuntimeException("User not found in UserToken.");
        }
        if ($userTokenCreatedAt === NULL || $userTokenExpiratedAt === NULL) {
            throw new RuntimeException("UserToken don't have some mandatory date.");
        }
        $userIdentifier = $user->getUserIdentifier();
        $payload = [
            "username" => $userIdentifier,
            "token" => $userTokenEntity->getToken(),
            "nbf" => $userTokenCreatedAt->getTimestamp(),
            "exp" => $userTokenExpiratedAt->getTimestamp()
        ];
        [
            "algo" =>  $JWT_ALGO,
            "secret" =>  $JWT_SECRET,
        ] = $this->_getJWTParam();
        return JWT::encode($payload, $JWT_SECRET, $JWT_ALGO);
    }

    /**
     * @param UserEntity $user
     * @return string
     * @throws JsonException
     */
    private function _refreshTokenAndJWT(UserEntity $user): string
    {
        $userToken = $this->_generateUserToken($user);
        $jwt = $this->_generateJWT($userToken);
        $user->addUserToken($userToken);
        $this->userORMService->persist($user);
        $this->userORMService->persist($userToken);
        $this->userORMService->flush();
        return $jwt;
    }

    /**
     * @param UserEntity $user
     * @return array[
     * "jwt" => string,
     * "role" => string,
     * ]
     * @throws JsonException
     */
    public function loginAndGetInfo(UserEntity $user): array
    {
        $jwt = $this->_refreshTokenAndJWT($user);
        $roleFrontName = $this->getRoleFrontName($user);
        return ["jwt" => $jwt, "role" => $roleFrontName];
    }

    /**
     * @param UserEntity $user
     * @return string
     */
    public function getRoleFrontName(UserEntity $user): string
    {
        $userMaxRole = $this->getMaxRole($user->getRoles());
        return $this->param->get($userMaxRole);
    }

    /**
     * Get info from JWT to check in our database if it's expirated nor too soon
     * @param string $jwt
     * @param bool $addUserToken
     * @return array[
     * "user" => UserEntity|null,
     * "userToken" => UserTokenEntity|null
     * ]
     */
    public function checkJWT(string $jwt, bool $addUserToken = FALSE): array
    {
        $response = ["user" => NULL, "userToken" => NULL];
        [
            "algo" =>  $JWT_ALGO,
            "secret" =>  $JWT_SECRET,
        ] = $this->_getJWTParam();
        $JWT_KEY = new Key($JWT_SECRET, $JWT_ALGO);
        $decodedJwt = (array)JWT::decode($jwt, $JWT_KEY);
        [
            "username" => $username,
            "token" => $token
        ] = $decodedJwt;
        $currentDateTime = new DateTime();
        $currentTimestamp = $currentDateTime->getTimestamp();
        $notBeforeTimestamp = NULL;
        $expirationTimestamp = NULL;
        if (empty($decodedJwt["nbf"]) === FALSE) {
            $notBeforeTimestamp = $decodedJwt["nbf"];
        }
        if (empty($decodedJwt["exp"]) === FALSE) {
            $expirationTimestamp = $decodedJwt["exp"];
        }
        if ($notBeforeTimestamp !== NULL && $notBeforeTimestamp > $currentTimestamp) {
            return $response;
        }
        if ($expirationTimestamp !== NULL && $expirationTimestamp < $currentTimestamp) {
            return $response;
        }
        $userEntity = $this->userORMService->findByUserIdentifiant($username);
        if ($userEntity === NULL) {
            return $response;
        }
        $userToken = $this->userTokenORMService->findByUserAndToken($userEntity, $token);
        if ($userToken === NULL) {
            return $response;
        }
        $createdDateTime = $userToken->getCreatedAt();
        $expirationDateTime = $userToken->getExpiratedAt();
        if ($createdDateTime === NULL || $expirationDateTime === NULL) {
            throw new RuntimeException("Some mandatory datetime are missing from UserToken");
        }
        if ($createdDateTime > $currentDateTime || $expirationDateTime < $currentDateTime) {
            return $response;
        }
        $response["user"] = $userEntity;
        if ($addUserToken === TRUE) {
            $response["userToken"] = $userToken;
        }
        return $response;
    }

    public function checkIsAdmin(UserEntity $user): bool
    {
        return in_array($this->param->get("ROLE_ADMIN_PLAIN_TEXT"), $user->getRoles(),  TRUE);
    }

    /**
     * @param array $roles
     * @return string
     */
    public function getMaxRole(array $roles): string
    {
        $roleOrder = $this->_getRoleOrder();
        $res = $this->param->get("ROLE_USER_PLAIN_TEXT");
        $currentRoleOrder = 0;
        foreach ($roles as $role) {
            if (isset($roleOrder[$role]) && $roleOrder[$role] > $currentRoleOrder) {
                $currentRoleOrder = $roleOrder[$role];
                $res = $role;
            }
        }
        return $res;
    }

    /**
     * @return array[string => int]
     */
    private function _getRoleOrder(): array
    {
        $admin = $this->param->get("ROLE_ADMIN_PLAIN_TEXT");
        $user = $this->param->get("ROLE_USER_PLAIN_TEXT");
        return [
            $user => 0,
            $admin => 1,
        ];
    }

    /**
     * @param UserEntity $user
     * @param string $plainPassword
     * @return string
     */
    private function _createPassword(UserEntity $user, #[\SensitiveParameter] string $plainPassword): string
    {
        return $this->userPasswordHasher->hashPassword($user, $plainPassword);
    }

    /**
     * @param UserEntity $user
     * @param string $plainPassword
     * @return UserEntity
     */
    public function editPassword(UserEntity $user, #[\SensitiveParameter] string $plainPassword): UserEntity
    {
        return $user->setPassword($this->_createPassword($user, $plainPassword));
    }

    /**
     * @param UserTokenEntity $userToken
     * @return void
     */
    public function logout(UserTokenEntity $userToken): void
    {
        $this->userORMService->remove($userToken);
        $this->userORMService->flush();
    }
}