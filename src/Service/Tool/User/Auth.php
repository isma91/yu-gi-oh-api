<?php

namespace App\Service\Tool\User;


use App\Entity\User as UserEntity;
use App\Service\Tool\User\ORM as UserORMService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class Auth
{
    private ParameterBagInterface $param;
    private UserPasswordHasherInterface $userPasswordHasher;
    private UserORMService $userORMService;

    public function __construct(
        ParameterBagInterface $param,
        UserPasswordHasherInterface $userPasswordHasher,
        UserORMService $userORMService
    )
    {
        $this->param = $param;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->userORMService = $userORMService;
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
     * @param string $username
     * @return string
     */
    private function _generateToken(string $username): string
    {
        return md5(uniqid($username, TRUE));
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
     * @param string $userIdentifier
     * @param string $token
     * @return string
     */
    private function _generateJWT(string $userIdentifier, string $token): string
    {
        $payload = [
            "username" => $userIdentifier,
            "token" => $token,
        ];
        [
            "algo" =>  $JWT_ALGO,
            "secret" =>  $JWT_SECRET,
        ] = $this->_getJWTParam();
        return JWT::encode($payload, $JWT_SECRET, $JWT_ALGO);
    }

    /**
     * @param UserEntity $user
     * @return array[
     * "jwt" => string,
     * "token" => string
     * ]
     */
    private function _refreshTokenAndJWT(UserEntity $user): array
    {
        $userIdentifier = $user->getUserIdentifier();
        $token = $this->_generateToken($userIdentifier);
        $jwt = $this->_generateJWT($userIdentifier, $token);
        $user->setToken($token);
        $this->userORMService->persist($user);
        $this->userORMService->flush();
        return ["jwt" => $jwt, "token" => $token];
    }

    /**
     * @param UserEntity $user
     * @return array[
     * "jwt" => string,
     * "role" => string,
     * ]
     */
    public function loginAndGetInfo(UserEntity $user): array
    {
        ["jwt" => $jwt] = $this->_refreshTokenAndJWT($user);
        $userMaxRole = $this->getMaxRole($user->getRoles());
        $roleFrontName = $this->param->get($userMaxRole);
        return ["jwt" => $jwt, "role" => $roleFrontName];
    }

    /**
     * @param string $jwt
     * @return UserEntity|null
     */
    public function checkJWT(string $jwt): ?UserEntity
    {
        [
            "algo" =>  $JWT_ALGO,
            "secret" =>  $JWT_SECRET,
        ] = $this->_getJWTParam();
        $JWT_KEY = new Key($JWT_SECRET, $JWT_ALGO);
        [
            "username" => $username,
            "token" => $token
        ] = (array)JWT::decode($jwt, $JWT_KEY);
        return $this->userORMService->findByUserIdentifiant($username);
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
     * @param UserEntity $user
     * @return void
     */
    public function logout(UserEntity $user): void
    {
        $user->setToken(NULL);
        $this->userORMService->persist($user);
        $this->userORMService->flush();
    }
}