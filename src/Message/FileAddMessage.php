<?php

declare(strict_types=1);

namespace App\Message;

class FileAddMessage
{
    public function __construct(public readonly int $fileId)
    {
    }
}
