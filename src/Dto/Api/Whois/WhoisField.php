<?php

declare(strict_types=1);

namespace App\Dto\Api\Whois;

final readonly class WhoisField
{
    public function __construct(
        public string $label,
        public string $value,
    ) {
    }
}

