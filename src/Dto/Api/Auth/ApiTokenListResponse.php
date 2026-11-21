<?php

namespace App\Dto\Api\Auth;

final readonly class ApiTokenListResponse
{
    /**
     * @param list<ApiTokenResponse> $items
     */
    public function __construct(
        public array $items,
    ) {
    }
}
