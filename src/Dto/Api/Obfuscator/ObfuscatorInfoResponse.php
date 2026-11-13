<?php

declare(strict_types=1);

namespace App\Dto\Api\Obfuscator;

final readonly class ObfuscatorInfoResponse
{
    public function __construct(
        public string $phpVersion,
        public int $maxCodeSize,
    ) {
    }
}

