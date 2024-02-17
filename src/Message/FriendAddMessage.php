<?php

declare(strict_types=1);

namespace App\Message;

readonly class FriendAddMessage
{
    public function __construct(public int $userId, public int $friendId)
    {
    }
}
