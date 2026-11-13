<?php

declare(strict_types=1);

namespace App\Dto\Api\News;

final readonly class NewsListItemResponse
{
    public function __construct(
        public int $id,
        public string $subject,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
    ) {
    }
}
