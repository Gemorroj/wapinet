<?php

declare(strict_types=1);

namespace App\Message;

readonly class FileAddMessage
{
    public function __construct(public int $fileId)
    {
    }
}
