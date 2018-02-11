<?php
namespace WapinetBundle\Helper;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use WapinetBundle\Entity\User;

/**
 * Timezone хэлпер
 */
class Timezone
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct (TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return \DateTimeZone|null
     */
    public function getTimezone()
    {
        $token = $this->tokenStorage->getToken();
        $user = (null !== $token ? $token->getUser() : null);

        if ($user instanceof User && null !== $user->getTimezone()) {
            return new \DateTimeZone($user->getTimezone());
        }

        return null;
    }
}
