<?php

declare(strict_types=1);

namespace App\Dto\Api\Gist;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GistListQuery
{
    public function __construct(
        #[Assert\Valid]
        public GistFilterQuery $filter = new GistFilterQuery(),
        #[Assert\Positive]
        public int $page = 1,
        #[Assert\Range(min: 1, max: 100)]
        public int $perPage = 20,
        #[Assert\Choice(choices: ['newest', 'oldest', 'relevance'])]
        public string $sort = 'newest',
    ) {
    }
}
