<?php

namespace App\Dto\Api\File;

use App\Dto\Api\ArchiveEntryResponse;

final readonly class ArchiveListResponse
{
    /**
     * @param list<ArchiveEntryResponse> $items
     */
    public function __construct(
        public array $items,
    ) {
    }
}
