<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
readonly class RequestMessage
{
    public function __construct(
        public \DateTime $dateTime,
        public string $ip,
        public string $browser,
        public string $path,
        public ?string $userIdentifier = null,
    ) {
    }
}
