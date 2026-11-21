<?php

namespace App\Dto\Api\User;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UserFilterQuery
{
    public function __construct(
        #[Assert\Length(max: 180)]
        public ?string $search = null,
    ) {
    }
}
