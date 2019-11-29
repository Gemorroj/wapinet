<?php

namespace App\Twig\Extension\User;

use App\Entity\User;
use App\Repository\FriendRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Friend extends AbstractExtension
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
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
        /** @var FriendRepository $friendRepository */
        $friendRepository = $this->em->getRepository(\App\Entity\Friend::class);
        $objFriend = $friendRepository->getFriend($user, $friend);

        return null !== $objFriend;
    }

    public function countFriends(User $user): int
    {
        /** @var FriendRepository $friendRepository */
        $friendRepository = $this->em->getRepository(\App\Entity\Friend::class);

        return $friendRepository->getFriendsCount($user);
    }

    public function countOnlineFriends(User $user): int
    {
        /** @var FriendRepository $friendRepository */
        $friendRepository = $this->em->getRepository(\App\Entity\Friend::class);

        return $friendRepository->getFriendsCount($user, new DateTime('now -'.User::LIFETIME));
    }
}
