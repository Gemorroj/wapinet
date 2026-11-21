<?php

declare(strict_types=1);

namespace App\Dto\Api\News;

use App\Dto\Api\User\UserSummary;

final readonly class NewsResponse
{
    public function __construct(
        public int $id,
        public string $subject,
        public string $body,
        public string $bodyHtml,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
        public ?UserSummary $createdBy,
    ) {
    }
}
