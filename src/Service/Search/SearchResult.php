<?php

namespace App\Service\Search;

final readonly class SearchResult
{
    /**
     * @param list<int> $ids
     */
    public function __construct(
        public array $ids,
        public int $total,
    ) {
    }
}
