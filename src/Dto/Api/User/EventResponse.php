<?php

declare(strict_types=1);

namespace App\Dto\Api\User;

final readonly class EventResponse
{
    public function __construct(
        public int $id,
        public \DateTimeImmutable $createdAt,
        public string $subject,
        public string $template,
        public ?string $url,
    ) {
    }
}
