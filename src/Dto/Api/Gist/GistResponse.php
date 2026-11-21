<?php

declare(strict_types=1);

namespace App\Dto\Api\Gist;

use App\Dto\Api\User\UserSummary;

final readonly class GistResponse
{
    public function __construct(
        public int $id,
        public string $subject,
        public string $body,
        public string $bodyHtml,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
        public ?UserSummary $user,
        public string $avatarUrl,
        public bool $isOnline,
        public bool $canEdit,
        public bool $canDelete,
    ) {
    }
}
