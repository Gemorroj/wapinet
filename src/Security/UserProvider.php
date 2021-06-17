<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserInterface as SecurityUserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * Constructor.
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findOneBy(['username' => $identifier]);

        if (!$user) {
            $e = new UserNotFoundException(\sprintf('Username "%s" does not exist.', $identifier));
            $e->setUserIdentifier($identifier);
            throw $e;
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername(string $username)
    {
        $user = $this->userRepository->findOneBy(['username' => $username]);

        if (!$user) {
            $e = new UserNotFoundException(\sprintf('Username "%s" does not exist.', $username));
            $e->setUserIdentifier($username);
            throw $e;
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(SecurityUserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Expected an instance of App\Entity\User, but got "%s".', \get_class($user)));
        }

        if (null === $reloadedUser = $this->userRepository->find($user->getId())) {
            $e = new UserNotFoundException(\sprintf('Username "%s" could not be reloaded.', $user->getUserIdentifier()));
            $e->setUserIdentifier($user->getUserIdentifier());
            throw $e;
        }

        return $reloadedUser;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass(string $class)
    {
        return User::class === $class || \is_subclass_of($class, User::class);
    }
}
