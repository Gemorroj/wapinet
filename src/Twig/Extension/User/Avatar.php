<?php

namespace App\Twig\Extension\User;

use App\Entity\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Avatar extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('wapinet_user_get_avatar_url', [$this, 'getAvatarUrl']),
        ];
    }

    public function getAvatarUrl(?User $user = null, ?int $size = 80): string
    {
        return '//gravatar.com/avatar/'.($user ? \md5($user->getEmail()) : '').'?d=mm'.($size ? '&s='.$size : '');
    }
}
