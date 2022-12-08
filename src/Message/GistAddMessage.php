<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Contracts\EventDispatcher\Event;

class GistAddMessage extends Event
{
    public function __construct(public readonly int $gistId)
    {
    }
}
