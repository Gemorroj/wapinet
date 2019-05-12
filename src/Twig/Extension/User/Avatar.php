<?php

namespace App\Twig\Extension\User;

use App\Entity\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Avatar extends AbstractExtension
{
    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('wapinet_user_get_avatar_url', [$this, 'getAvatarUrl']),
        ];
    }

    /**
     * @param User|null $user
     * @param int|null  $size
     *
     * @return string
     */
    public function getAvatarUrl(User $user = null, ?int $size = 80): string
    {
        return '//gravatar.com/avatar/'.($user ? \md5($user->getEmailCanonical()) : '').'?d=mm'.($size ? '&s='.$size : '');
    }
}
