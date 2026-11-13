<?php

declare(strict_types=1);

namespace App\Dto\Api\Gist;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GistCreateRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 5000)]
        public string $subject = '',
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 5000)]
        public string $body = '',
        public ?string $bCheck = null,
    ) {
    }
}
