<?php

declare(strict_types=1);

namespace App\Dto\Api\Config;

final readonly class CountryResponse
{
    public function __construct(
        public string $code,
        public string $name,
    ) {
    }
}
