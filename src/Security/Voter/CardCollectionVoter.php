<?php

namespace App\Security\Voter;

use App\Entity\CardCollection as CardCollectionEntity;
use App\Entity\User as UserEntity;
use App\Service\Logger as LoggerService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CardCollectionVoter extends Voter
{
    public const INFO = 'CARD_COLLECTION_GET_INFO';
    public const UPDATE = 'CARD_COLLECTION_UPDATE';
    public const DELETE = 'CARD_COLLECTION_DELETE';
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
        return in_array($attribute, [self::INFO, self::UPDATE, self::DELETE])
            && $subject instanceof CardCollectionEntity;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserEntity || !$subject instanceof CardCollectionEntity) {
            return FALSE;
        }
        $collectionUser = $subject->getUser();
        //we don't take any chance of problems by skipping collection without user
        if ($collectionUser === NULL) {
            $this->loggerService->addLog(
                sprintf(
                    "No User found for Collection id => %d", $subject->getId()
                )
            );
            return FALSE;
        }
        $sameUser = $collectionUser->getId() === $user->getId();
        //give all access to admin
        if ($this->security->isGranted("ROLE_ADMIN") === TRUE) {
            return TRUE;
        }
        if ($attribute === self::INFO) {
            return $subject->isIsPublic() === TRUE || $sameUser;
        }
        //to update/delete it must be the user of collection
        return $sameUser;
    }
}
