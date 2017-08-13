<?php
namespace WapinetBundle\Security;

use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use WapinetBundle\Entity\File;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use WapinetUserBundle\Entity\User;

class FileVoter extends Voter
{
    const DELETE = 'DELETE';
    const EDIT = 'EDIT';

    private $decisionManager;

    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, array(self::DELETE, self::EDIT))) {
            return false;
        }

        if (!$subject instanceof File) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        if (!($user instanceof User)) {
            return false;
        }

        if ($user === $subject->getUser()) {
            return true;
        }

        if ($this->decisionManager->decide($token, array('ROLE_ADMIN'))) {
            return true;
        }

        return false;
    }
}