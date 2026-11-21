<?php

declare(strict_types=1);

namespace App\Dto\Api\User;

final readonly class UserListItem
{
    public function __construct(
        public int $id,
        public string $username,
        public string $avatarUrl,
        public bool $isOnline,
    ) {
    }
}
