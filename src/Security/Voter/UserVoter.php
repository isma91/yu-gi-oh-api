<?php

namespace App\Security\Voter;

use App\Service\Logger as LoggerService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use App\Entity\User as UserEntity;
class UserVoter extends Voter
{
    public const USER_ADMIN_INFO = 'USER_GET_USER_ADMIN_INFO';
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
        return $attribute === self::USER_ADMIN_INFO && $subject instanceof UserEntity;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserEntity || !$subject instanceof UserEntity) {
            return FALSE;
        }
        $isGranted = $this->security->isGranted("ROLE_ADMIN");
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
}
