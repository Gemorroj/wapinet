<?php

declare(strict_types=1);

namespace App\Dto\Api\User;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 180)]
        public string $username = '',
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email = '',
        #[Assert\NotBlank]
        #[Assert\Length(min: 6, max: 4096)]
        public string $password = '',
        #[Assert\Length(max: 4096)]
        public ?string $captchaToken = null,
    ) {
    }
}
