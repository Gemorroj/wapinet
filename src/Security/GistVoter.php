<?php

namespace App\Security;

use App\Entity\Gist;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class GistVoter extends Voter
{
    public const DELETE = 'DELETE';
    public const EDIT = 'EDIT';

    private AccessDecisionManagerInterface $decisionManager;

    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

    protected function supports(string $attribute, $subject): bool
    {
        if (!\in_array($attribute, [self::DELETE, self::EDIT], true)) {
            return false;
        }

        if (!$subject instanceof Gist) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!($user instanceof User)) {
            return false;
        }

        if ($user === $subject->getUser()) {
            return true;
        }

        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        return false;
    }
}
