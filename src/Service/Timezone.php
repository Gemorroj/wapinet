<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Timezone
{
    public function __construct(private TokenStorageInterface $tokenStorage)
    {
    }

    public function getTimezone(): ?\DateTimeZone
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if ($user instanceof User && $user->getTimezone()) {
            return new \DateTimeZone($user->getTimezone());
        }

        return null;
    }
}
