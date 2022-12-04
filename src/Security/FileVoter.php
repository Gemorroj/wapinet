<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\File;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FileVoter extends Voter
{
    public const DELETE = 'DELETE';
    public const EDIT = 'EDIT';

    public function __construct(private AccessDecisionManagerInterface $decisionManager)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        if (!\in_array($attribute, [self::DELETE, self::EDIT], true)) {
            return false;
        }

        if (!$subject instanceof File) {
            return false;
        }

        return true;
    }

    public function supportsType(string $subjectType): bool
    {
        return \is_a($subjectType, File::class, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!($user instanceof User)) {
            return false;
        }

        if ($user->isEqualTo($subject->getUser())) {
            return true;
        }

        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        return false;
    }
}
