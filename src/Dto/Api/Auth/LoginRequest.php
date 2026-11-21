<?php

namespace App\Dto\Api\Auth;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request contract for POST /api/auth/login.
 *
 * Authentication itself is handled by Symfony's json_login authenticator; the constraints
 * below describe the request body in OpenAPI.
 */
final readonly class LoginRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 180)]
        public string $username = '',
        #[Assert\NotBlank]
        #[Assert\Length(max: 4096)]
        public string $password = '',
        #[Assert\Length(max: 100)]
        public ?string $deviceName = null,
    ) {
    }
}
