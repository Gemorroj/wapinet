<?php

declare(strict_types=1);

namespace App\Dto\Api\Hash;

final readonly class HashResponse
{
    /**
     * @param array<string, string> $hashes
     */
    public function __construct(
        public array $hashes,
    ) {
    }
}

