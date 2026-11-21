<?php

declare(strict_types=1);

namespace App\Dto\Api\Gist;

final readonly class GistListResponse
{
    /**
     * @param list<GistListItem> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }
}
