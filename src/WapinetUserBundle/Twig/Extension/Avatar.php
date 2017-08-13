<?php

namespace WapinetUserBundle\Twig\Extension;

use WapinetUserBundle\Entity\User;

class Avatar extends \Twig_Extension
{
    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('wapinet_user_get_avatar_url', array($this, 'getAvatarUrl')),
        );
    }

    /**
     * @param User|null $user
     * @param int|null $size
     * @return string
     */
    public function getAvatarUrl(User $user = null, $size = 80)
    {
        return '//gravatar.com/avatar/' . ($user ? \md5($user->getEmailCanonical()) : '') . '?d=mm' . ($size ? '&s=' . (int)$size : '');
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_user_avatar';
    }
}
