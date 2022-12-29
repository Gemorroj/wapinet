<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class Timezone
{
    public function __construct(private Security $security)
    {
    }

    public function getTimezone(): ?\DateTimeZone
    {
        $user = $this->security->getUser();

        if ($user instanceof User && $user->getTimezone()) {
            return new \DateTimeZone($user->getTimezone());
        }

        return null;
    }
}
