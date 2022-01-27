<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class FriendEvent extends Event
{
    public const FRIEND_ADD = 'wapinet_user.friend.add';
    public const FRIEND_DELETE = 'wapinet_user.friend.delete';

    public function __construct(private User $user, private User $friend)
    {
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
