<?php

declare(strict_types=1);

namespace App\Dto\Api\Archiver;

use App\Dto\Api\ArchiveEntryResponse;

final readonly class ArchiveResponse
{
    /**
     * @param list<ArchiveEntryResponse> $items
     */
    public function __construct(
        public string $archive,
        public array $items,
        public int $size,
        public ?string $format = null,
        public ?int $sourceSize = null,
    ) {
    }
}
