<?php

namespace App\Security\Voter;

use App\Service\Logger as LoggerService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use App\Entity\Deck as DeckEntity;
use App\Entity\User as UserEntity;

class DeckVoter extends Voter
{
    public const INFO = 'DECK_INFO';
    public const UPDATE = 'DECK_UPDATE';
    public const DELETE = 'DECK_DELETE';
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
            && $subject instanceof DeckEntity;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserEntity || !$subject instanceof DeckEntity) {
            return FALSE;
        }
        $deckUser = $subject->getUser();
        //we don't take any chance of problems by skipping deck without user
        if ($deckUser === NULL) {
            $this->loggerService->addLog(
                sprintf(
                    "No User found for Deck id => %d", $subject->getId()
                )
            );
            return FALSE;
        }
        $sameUser = $deckUser->getId() === $user->getId();
        //give all access to admin
        if ($this->security->isGranted("ROLE_ADMIN") === TRUE) {
            return TRUE;
        }
        if ($attribute === self::INFO) {
            return $subject->isIsPublic() === TRUE || $sameUser;
        }
        //to update/delete it must be the user of deck
        return $sameUser;
    }
}
