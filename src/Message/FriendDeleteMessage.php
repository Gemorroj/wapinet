<?php

declare(strict_types=1);

namespace App\Message;

class FriendDeleteMessage
{
    public function __construct(public readonly int $userId, public readonly int $friendId)
    {
    }
}
