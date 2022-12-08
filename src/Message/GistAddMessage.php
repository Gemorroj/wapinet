<?php

declare(strict_types=1);

namespace App\Message;

class GistAddMessage
{
    public function __construct(public readonly int $gistId)
    {
    }
}
