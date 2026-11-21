<?php

declare(strict_types=1);

namespace App\Dto\Api\Guestbook;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GuestbookCreateRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 5000)]
        public string $message = '',
        #[Assert\Length(max: 4096)]
        public ?string $captchaToken = null,
        public ?string $bCheck = null,
    ) {
    }
}
