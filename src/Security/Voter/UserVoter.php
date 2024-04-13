<?php

namespace App\Security\Voter;

use App\Entity\UserToken as UserTokenEntity;
use App\Service\Logger as LoggerService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use App\Entity\User as UserEntity;
class UserVoter extends Voter
{
    public const USER_ADMIN_INFO = 'USER_GET_USER_ADMIN_INFO';
    public const USER_REVOKE_TOKEN = 'USER_REVOKE_TOKEN';
    private LoggerService $loggerService;
    private Security $security;

    public function __construct(LoggerService $loggerService, Security $security)
    {
        $this->loggerService = $loggerService;
        $this->loggerService->setIsCron(FALSE)->setLevel(LoggerService::ERROR);
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::USER_ADMIN_INFO, self::USER_REVOKE_TOKEN], TRUE) &&
            ($subject instanceof UserEntity || $subject instanceof UserTokenEntity);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserEntity) {
            return FALSE;
        }
        $isGranted = $this->security->isGranted("ROLE_ADMIN");
        if ($attribute === self::USER_ADMIN_INFO) {
            if (!$subject instanceof UserEntity) {
                return FALSE;
            }
            if ($isGranted === FALSE) {
                $this->loggerService->addLog(
                    sprintf(
                        "User non-admin '%s' try to go to the route '%s'",
                        $user->getUsername() ?? "",
                        $attribute
                    )
                );
            }
            return $isGranted;
        }

        if ($attribute === self::USER_REVOKE_TOKEN) {
            if (!$subject instanceof UserTokenEntity) {
                return FALSE;
            }
            if ($isGranted === TRUE) {
                return TRUE;
            }
            $userTokenUser = $subject->getUser();
            if ($userTokenUser === NULL) {
                $this->loggerService->addLog(sprintf("UserToken id %d without user", $subject->getId()));
                return FALSE;
            }
            return $userTokenUser->getId() === $user->getId();
        }
        return FALSE;
    }
}
