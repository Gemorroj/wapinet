<?php

declare(strict_types=1);

namespace App\Dto\Api\News;

final readonly class NewsListResponse
{
    /**
     * @param list<NewsListItemResponse> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }
}
