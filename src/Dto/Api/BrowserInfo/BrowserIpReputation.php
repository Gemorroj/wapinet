<?php

declare(strict_types=1);

namespace App\Dto\Api\BrowserInfo;

final readonly class BrowserIpReputation
{
    public function __construct(
        public bool $appears,
        public ?float $confidence,
        public ?float $frequency,
        public ?string $lastSeen,
        public bool $torExit,
        public ?string $country,
        public ?int $asn,
        public ?string $delegated,
    ) {
    }
}

