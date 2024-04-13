<?php

namespace App\Security;

use App\Repository\UserRepository;
use App\Service\Tool\User\Auth as UserAuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JWTAuthenticator extends AbstractAuthenticator
{
    private UserAuthService $userAuthService;
    private UserRepository $userRepository;

    public function __construct(
        UserAuthService $userAuthService,
        UserRepository $userRepository
    )
    {
        $this->userAuthService = $userAuthService;
        $this->userRepository = $userRepository;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request): bool
    {
        return $request->headers->has('Authorization');
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $firewallName
     * @return Response|null
     */
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response
    {
        // on success, let the request continue
        return null;
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return JsonResponse
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse(
            [
                "error" => $exception->getMessage(),
                "code" => $exception->getCode()
            ],
            Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * Called when authentication is needed, but it's not sent
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return JsonResponse
     */
    public function start(Request $request, AuthenticationException $authException = NULL): JsonResponse
    {
        return new JsonResponse(
            [
                "error" => "An authentication is mandatory."
            ],
            Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * @param Request $request
     * @return Passport
     */
    public function authenticate(Request $request): Passport
    {
        try {
            $bearer = $request->headers->get('Authorization');
            $credentials = str_replace('Bearer ', '', $bearer);
            [
                "user" => $userEntity,
                "userToken" => $userToken
            ] = $this->userAuthService->checkJWT($credentials, TRUE);
            if ($userEntity === NULL) {
                throw new UserNotFoundException("");
            }
            $userBadge = new UserBadge(
                $userEntity->getUserIdentifier(),
                function (string $userIdentifier): ?UserInterface
                {
                    return $this->userRepository->findOneBy(['username' => $userIdentifier]);
                }
            );
            $user = $userBadge->getUser();
            if ($user->getDeletedAt() !== NULL) {
                throw new AuthenticationException(
                    "User deleted, please contact us for more information.",
                    Response::HTTP_UNAUTHORIZED
                );
            }
            $this->userAuthService->extendUserTokenAndCreateUserTracking($userToken);
            return new SelfValidatingPassport($userBadge);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            if ($e instanceof  UserNotFoundException) {
                $message = "User not found.";
                $code = Response::HTTP_UNAUTHORIZED;
            }
            throw new AuthenticationException($message, $code);
        }
    }
}