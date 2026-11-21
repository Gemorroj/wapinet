<?php

declare(strict_types=1);

namespace App\Dto\Api\Online;

final readonly class OnlineListResponse
{
    /**
     * @param list<OnlineItemResponse> $items
     */
    public function __construct(
        public array $items,
        public int $count,
    ) {
    }
}
