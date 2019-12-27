<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class FriendEvent extends Event
{
    const FRIEND_ADD = 'wapinet_user.friend.add';
    const FRIEND_DELETE = 'wapinet_user.friend.delete';

    private $user;
    private $friend;

    public function __construct(User $user, User $friend)
    {
        $this->user = $user;
        $this->friend = $friend;
    }

    /**
     * Returns the user for this event.
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Returns the friend for this event.
     */
    public function getFriend(): User
    {
        return $this->friend;
    }
}
