<?php

declare(strict_types=1);

namespace App\Dto\Api\User;

final readonly class EventListResponse
{
    /**
     * @param list<EventResponse> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }
}
