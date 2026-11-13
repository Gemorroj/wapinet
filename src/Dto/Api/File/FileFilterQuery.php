<?php

namespace App\Dto\Api\File;

use App\Enum\FileCategory;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class FileFilterQuery
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $q = null,
        #[Assert\Choice(callback: [FileCategory::class, 'values'])]
        public ?string $category = null,
        #[Assert\Choice(choices: ['all', 'today', 'yesterday'])]
        public string $date = 'all',
        #[Assert\Length(max: 255)]
        public ?string $tag = null,
        #[Assert\Length(max: 180)]
        public ?string $user = null,
        public bool $mine = false,
        public ?bool $hidden = null,
    ) {
    }
}
