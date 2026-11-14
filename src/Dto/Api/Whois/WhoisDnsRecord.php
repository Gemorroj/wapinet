<?php

declare(strict_types=1);

namespace App\Dto\Api\Whois;

final readonly class WhoisDnsRecord
{
    public function __construct(
        public string $type,
        public string $value,
    ) {
    }
}
