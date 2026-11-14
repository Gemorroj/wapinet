<?php

namespace App\Dto\Api;

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
