<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
readonly class FriendDeleteMessage
{
    public function __construct(public int $userId, public int $friendId)
    {
    }
}
