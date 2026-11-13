<?php

namespace App\Dto\Api\User;

use App\Entity\User;

final readonly class UserSummary
{
    public function __construct(
        public int $id,
        public string $username,
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self((int) $user->getId(), $user->getUsername());
    }
}
