<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
readonly class GistAddMessage
{
    public function __construct(public int $gistId)
    {
    }
}
