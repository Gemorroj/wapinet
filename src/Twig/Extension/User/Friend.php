<?php

namespace App\Twig\Extension\User;

use App\Entity\User;
use App\Repository\FriendRepository;
use Doctrine\ORM\EntityManagerInterface;

class Friend extends \Twig_Extension
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @param EntityManagerInterface $em
     */
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
            new \Twig_SimpleFunction('wapinet_user_is_friends', [$this, 'isFriends']),
            new \Twig_SimpleFunction('wapinet_user_count_friends', [$this, 'countFriends']),
            new \Twig_SimpleFunction('wapinet_user_count_online_friends', [$this, 'countOnlineFriends']),
        ];
    }

    /**
     * @param User $user
     * @param User $friend
     *
     * @return bool
     */
    public function isFriends(User $user, User $friend): bool
    {
        /** @var FriendRepository $friendRepository */
        $friendRepository = $this->em->getRepository(\App\Entity\Friend::class);
        $objFriend = $friendRepository->getFriend($user, $friend);

        return null !== $objFriend;
    }

    /**
     * @param User $user
     *
     * @return int
     */
    public function countFriends(User $user): int
    {
        /** @var FriendRepository $friendRepository */
        $friendRepository = $this->em->getRepository(\App\Entity\Friend::class);

        return $friendRepository->getFriendsCount($user);
    }

    /**
     * @param User $user
     *
     * @return int
     */
    public function countOnlineFriends(User $user): int
    {
        /** @var FriendRepository $friendRepository */
        $friendRepository = $this->em->getRepository(\App\Entity\Friend::class);

        return $friendRepository->getFriendsCount($user, new \DateTime('now -'.User::LIFETIME));
    }
}
