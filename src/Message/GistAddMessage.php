<?php

declare(strict_types=1);

namespace App\Message;

readonly class GistAddMessage
{
    public function __construct(public int $gistId)
    {
    }
}
