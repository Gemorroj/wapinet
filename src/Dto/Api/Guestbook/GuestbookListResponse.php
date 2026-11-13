<?php

declare(strict_types=1);

namespace App\Dto\Api\Guestbook;

final readonly class GuestbookListResponse
{
    /**
     * @param list<GuestbookMessageResponse> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }
}
