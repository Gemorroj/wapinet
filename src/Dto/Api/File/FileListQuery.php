<?php

namespace App\Dto\Api\File;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class FileListQuery
{
    public function __construct(
        #[Assert\Valid]
        public FileFilterQuery $filter = new FileFilterQuery(),
        #[Assert\Positive]
        public int $page = 1,
        #[Assert\Range(min: 1, max: 100)]
        public int $perPage = 20,
        #[Assert\Choice(choices: ['newest', 'oldest', 'name', 'views', 'relevance'])]
        public string $sort = 'newest',
    ) {
    }
}
