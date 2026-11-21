<?php

declare(strict_types=1);

namespace App\Dto\Api\Gist;

use App\Dto\Api\User\UserSummary;

final readonly class GistListItem
{
    public function __construct(
        public int $id,
        public string $subject,
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
