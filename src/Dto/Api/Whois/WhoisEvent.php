<?php

declare(strict_types=1);

namespace App\Dto\Api\Whois;

final readonly class WhoisEvent
{
    public function __construct(
        public string $action,
        public string $date,
    ) {
    }
}

