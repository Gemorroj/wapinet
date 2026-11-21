<?php

namespace App\Dto\Api\Tag;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TagFilterQuery
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $q = null,
    ) {
    }
}
