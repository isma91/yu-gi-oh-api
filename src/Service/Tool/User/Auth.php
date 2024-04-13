<?php

namespace App\Service\Tool\User;

use App\Entity\User as UserEntity;
use App\Entity\UserToken as UserTokenEntity;
use App\Entity\UserTracking as UserTrackingEntity;
use App\Service\Tool\User\ORM as UserORMService;
use App\Service\Tool\UserToken\ORM as UserTokenORMService;
use App\Service\Tool\UserTracking\Entity as UserTrackingEntityService;
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
    private UserTrackingEntityService $userTrackingEntityService;
    private string $userTokenDuration = "+3 days";

    public function __construct(
        ParameterBagInterface $param,
        UserPasswordHasherInterface $userPasswordHasher,
        UserORMService $userORMService,
        UserTokenORMService $userTokenORMService,
        UserTrackingEntityService $userTrackingEntityService
    )
    {
        $this->param = $param;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->userORMService = $userORMService;
        $this->userTokenORMService = $userTokenORMService;
        $this->userTrackingEntityService = $userTrackingEntityService;
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
        $expDatetime = new DateTime();
        $expDatetime->modify($this->userTokenDuration);
        return $userTokenEntity->setToken($token)
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
            "token" => $userTokenEntity->getToken()
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
        $userTracking = $this->userTrackingEntityService->createEntity();
        $jwt = $this->_generateJWT($userToken);
        $userToken->addUserTracking($userTracking);
        $userTracking->setUserToken($userToken);
        $user->addUserToken($userToken);
        $this->userORMService->persist($user);
        $this->userORMService->persist($userToken);
        $this->userORMService->persist($userTracking);
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
            "token" => $token,
        ] = $decodedJwt;
        $currentDateTime = new DateTime();
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

    /**
     * Used every time the user go to a protected route (route needed an auth)
     * We create a new UserTracking entity to see if he changes some static info (ip, location etc...)
     * And we also add some time to the token to be expirated after $userTokenDuration
     * So if the user use regularly this token he can use it forever
     * @param UserTokenEntity $userToken
     * @return void
     */
    public function extendUserTokenAndCreateUserTracking(UserTokenEntity $userToken): void
    {
        try {
            $userTracking = $this->userTrackingEntityService->createEntity();
            $expDatetime = new DateTime();
            $expDatetime->modify($this->userTokenDuration);
            $userToken->setExpiratedAt($expDatetime)
                ->incrementNbUsage()
                ->addUserTracking($userTracking);
            $userTracking->setUserToken($userToken);
            $this->userORMService->persist($userToken);
            $this->userORMService->persist($userTracking);
            $this->userORMService->flush();
        } catch (\Exception $e) {
        }
    }
}