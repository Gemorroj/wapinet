<?php

declare(strict_types=1);

namespace App\Dto\Api\User;

final readonly class RegisterResponse
{
    public function __construct(
        public string $username,
    ) {
    }
}
