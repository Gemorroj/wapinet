<?php

namespace App\Dto\Api\User;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UserListQuery
{
    public function __construct(
        #[Assert\Valid]
        public UserFilterQuery $filter = new UserFilterQuery(),
        #[Assert\Positive]
        public int $page = 1,
    ) {
    }
}
