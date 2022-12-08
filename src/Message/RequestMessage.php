<?php

declare(strict_types=1);

namespace App\Message;

class RequestMessage
{
    public function __construct(
        public readonly \DateTime $dateTime,
        public readonly string $ip,
        public readonly string $browser,
        public readonly string $path,
        public readonly ?string $userIdentifier,
    ) {
    }
}
