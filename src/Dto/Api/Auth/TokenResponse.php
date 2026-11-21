<?php

namespace App\Dto\Api\Auth;

use App\Dto\Api\User\UserResponse;

final readonly class TokenResponse
{
    public function __construct(
        public string $token,
        public \DateTimeImmutable $expiresAt,
        public UserResponse $user,
    ) {
    }
}
