<?php
namespace Wapinet\Bundle\Helper;

use Symfony\Component\Security\Core\SecurityContext;
use Wapinet\UserBundle\Entity\User;

/**
 * Timezone хэлпер
 */
class Timezone
{
    /**
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @param SecurityContext $securityContext
     */
    public function __construct (SecurityContext $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * @return \DateTimeZone|null
     */
    public function getTimezone()
    {
        $token = $this->securityContext->getToken();
        $user = (null !== $token ? $token->getUser() : null);

        if ($user instanceof User && null !== $user->getTimezone()) {
            return new \DateTimeZone($user->getTimezone());
        }

        return null;
    }
}
