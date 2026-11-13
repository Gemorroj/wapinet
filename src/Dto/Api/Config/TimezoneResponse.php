<?php

declare(strict_types=1);

namespace App\Dto\Api\Config;

final readonly class TimezoneResponse
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }
}
