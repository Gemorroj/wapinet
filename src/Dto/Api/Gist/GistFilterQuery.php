<?php

declare(strict_types=1);

namespace App\Dto\Api\Gist;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GistFilterQuery
{
    public function __construct(
        #[Assert\Length(max: 5000)]
        public ?string $q = null,
        #[Assert\Length(max: 180)]
        public ?string $user = null,
    ) {
    }
}
