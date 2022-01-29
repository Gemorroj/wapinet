<?php

namespace App\Twig\Extension\User;

use App\Entity\User;
use App\Repository\UserFriendRepository;
use DateTime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Friend extends AbstractExtension
{
    private UserFriendRepository $friendRepository;

    public function __construct(UserFriendRepository $friendRepository)
    {
        $this->friendRepository = $friendRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wapinet_user_is_friends', [$this, 'isFriends']),
            new TwigFunction('wapinet_user_count_friends', [$this, 'countFriends']),
            new TwigFunction('wapinet_user_count_online_friends', [$this, 'countOnlineFriends']),
        ];
    }

    public function isFriends(User $user, User $friend): bool
    {
        $objFriend = $this->friendRepository->getFriend($user, $friend);

        return null !== $objFriend;
    }

    public function countFriends(User $user): int
    {
        return $this->friendRepository->getFriendsCount($user);
    }

    public function countOnlineFriends(User $user): int
    {
        return $this->friendRepository->getFriendsCount($user, new DateTime('now -'.User::LIFETIME));
    }
}
