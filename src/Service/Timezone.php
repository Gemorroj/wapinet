<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Timezone
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function getTimezone(): ?\DateTimeZone
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        if ($user instanceof User && null !== $user->getTimezone()) {
            return new \DateTimeZone($user->getTimezone());
        }

        return null;
    }
}
