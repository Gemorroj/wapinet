<?php

declare(strict_types=1);

namespace App\Dto\Api\Obfuscator;

final readonly class ObfuscatorResponse
{
    public function __construct(
        public string $code,
        public int $sourceSize,
        public int $resultSize,
    ) {
    }
}
