<?php

declare(strict_types=1);

namespace App\Dto\Api\Whois;

final readonly class WhoisHostInfo
{
    /**
     * @param list<string> $ips
     */
    public function __construct(
        public string $hostname,
        public array $ips,
        public bool $confirmed,
    ) {
    }
}
