<?php

declare(strict_types=1);

namespace App\Service\Network;

final readonly class OutboundTarget
{
    public function __construct(
        public string $host,
        public int $port,
        public string $ip,
        public string $scheme,
    ) {
    }
}
