<?php

namespace App\Dto\Api\User;

use App\Entity\User;

final readonly class UserResponse
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public int $id,
        public string $username,
        public string $email,
        public array $roles,
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self(
            (int) $user->getId(),
            $user->getUsername(),
            $user->getEmail(),
            $user->getRoles(),
        );
    }
}
