<?php

namespace App\Twig\Extension\User;

use App\Entity\User;

class Avatar extends \Twig_Extension
{
    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('wapinet_user_get_avatar_url', [$this, 'getAvatarUrl']),
        ];
    }

    /**
     * @param User|null $user
     * @param int|null $size
     * @return string
     */
    public function getAvatarUrl(User $user = null, ?int $size = 80): string
    {
        return '//gravatar.com/avatar/' . ($user ? \md5($user->getEmailCanonical()) : '') . '?d=mm' . ($size ? '&s=' . $size : '');
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName(): string
    {
        return 'wapinet_user_avatar';
    }
}
