<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Contracts\EventDispatcher\Event;

class FriendDeleteMessage extends Event
{
    public function __construct(public readonly int $userId, public readonly int $friendId)
    {
    }
}