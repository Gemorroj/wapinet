<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Expected an instance of App\Entity\User, but got "%s".', \get_class($user)));
        }

        $this->userRepository->upgradePassword($user, $newHashedPassword);
    }

    public function loadUserByIdentifier(string $identifier): User
    {
        $user = $this->userRepository->loadUserByIdentifier($identifier);

        if (!$user) {
            $e = new UserNotFoundException(\sprintf('Username "%s" does not exist.', $identifier));
            $e->setUserIdentifier($identifier);
            throw $e;
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): User
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Expected an instance of App\Entity\User, but got "%s".', \get_class($user)));
        }

        // return $user;

        $reloadedUser = $this->userRepository->loadUserByIdentifier($user->getUserIdentifier());
        if (!$reloadedUser) {
            $e = new UserNotFoundException(\sprintf('Username "%s" could not be reloaded.', $user->getUserIdentifier()));
            $e->setUserIdentifier($user->getUserIdentifier());
            throw $e;
        }

        return $reloadedUser;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || \is_subclass_of($class, User::class);
    }
}
