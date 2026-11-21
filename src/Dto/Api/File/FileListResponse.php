<?php

namespace App\Dto\Api\File;

final readonly class FileListResponse
{
    /**
     * @param list<FileListItem> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }
}
