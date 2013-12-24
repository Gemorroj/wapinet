<?php

namespace Wapinet\UserBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Wapinet\UserBundle\Entity\User;

class FriendEvent extends Event
{
    const FRIEND_ADD = 'wapinet_user.friend.add';
    const FRIEND_DELETE = 'wapinet_user.friend.delete';

    private $user;
    private $friend;

    /**
     * Constructs an event.
     *
     * @param User $user
     * @param User $friend
     */
    public function __construct(User $user, User $friend)
    {
        $this->user = $user;
    }

    /**
     * Returns the user for this event.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }


    /**
     * Returns the friend for this event.
     *
     * @return User
     */
    public function getFriend()
    {
        return $this->friend;
    }
}
