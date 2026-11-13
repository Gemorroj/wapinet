<?php

declare(strict_types=1);

namespace App\Message;

use App\Enum\FileConversion;
use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
readonly class FileConvertMessage
{
    public function __construct(
        public int $fileId,
        public FileConversion $format,
    ) {
    }
}
