<?php

namespace App\Dto\Api\Archive;

final readonly class ArchiveEntryResponse
{
    public function __construct(
        public string $path,
        public int $size,
        public bool $isDirectory,
        public bool $isEncrypted,
    ) {
    }
}
