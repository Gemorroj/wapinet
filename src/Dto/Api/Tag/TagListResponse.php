<?php

declare(strict_types=1);

namespace App\Dto\Api\Tag;

final readonly class TagListResponse
{
    /**
     * @param list<TagListItem> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }
}
