<?php

namespace WapinetBundle\Twig\Extension\User;

use Doctrine\ORM\EntityManagerInterface;
use WapinetBundle\Entity\User;

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
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('wapinet_user_is_friends', array($this, 'isFriends')),
            new \Twig_SimpleFunction('wapinet_user_count_friends', array($this, 'countFriends')),
            new \Twig_SimpleFunction('wapinet_user_count_online_friends', array($this, 'countOnlineFriends')),
        );
    }

    /**
     * @param User $user
     * @param User $friend
     *
     * @return bool
     */
    public function isFriends(User $user, User $friend)
    {
        $friendRepository = $this->em->getRepository(\WapinetBundle\Entity\Friend::class);
        $objFriend = $friendRepository->getFriend($user, $friend);

        return (null !== $objFriend);
    }


    /**
     * @param User $user
     * @return int
     */
    public function countFriends(User $user)
    {
        $friendRepository = $this->em->getRepository(\WapinetBundle\Entity\Friend::class);

        return $friendRepository->getFriendsCount($user);
    }

    /**
     * @param User $user
     * @return int
     */
    public function countOnlineFriends(User $user)
    {
        $friendRepository = $this->em->getRepository(\WapinetBundle\Entity\Friend::class);

        return $friendRepository->getFriendsCount($user, new \DateTime('now -' . User::LIFETIME));
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_user_friend';
    }
}