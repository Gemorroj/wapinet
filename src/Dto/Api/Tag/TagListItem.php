<?php

declare(strict_types=1);

namespace App\Dto\Api\Tag;

final readonly class TagListItem
{
    public function __construct(
        public int $id,
        public string $name,
        public int $count,
    ) {
    }
}
