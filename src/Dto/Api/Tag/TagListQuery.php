<?php

namespace App\Dto\Api\Tag;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TagListQuery
{
    public function __construct(
        #[Assert\Valid]
        public TagFilterQuery $filter = new TagFilterQuery(),
        #[Assert\Positive]
        public int $page = 1,
        #[Assert\Range(min: 1, max: 100)]
        public int $perPage = 50,
    ) {
    }
}
