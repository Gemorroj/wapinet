<?php

declare(strict_types=1);

namespace App\Dto\Api\Http;

final readonly class HttpCallHeader
{
    public function __construct(
        public string $name,
        public string $value,
    ) {
    }
}
