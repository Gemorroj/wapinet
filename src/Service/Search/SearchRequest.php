<?php

declare(strict_types=1);

namespace App\Service\Search;

final readonly class SearchRequest
{
    /**
     * @param list<array<string, mixed>>  $filters Manticore filter clauses
     * @param list<array<string, string>> $sort    Manticore sort clauses
     */
    public function __construct(
        public string $index,
        public string $text,
        public array $filters = [],
        public array $sort = [],
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}
