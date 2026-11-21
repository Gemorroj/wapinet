<?php

declare(strict_types=1);

namespace App\Dto\Api\Online;

final readonly class OnlineItemResponse
{
    public function __construct(
        public int $id,
        public \DateTimeImmutable $datetime,
        public string $browser,
        public ?string $ip,
        public ?string $path,
    ) {
    }
}
