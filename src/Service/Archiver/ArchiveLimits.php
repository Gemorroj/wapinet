<?php

declare(strict_types=1);

namespace App\Service\Archiver;

/**
 * Per-archive limits. They bound one anonymous archive, not the whole service:
 * disk pressure is additionally guarded by minFreeSpace before every write.
 */
final readonly class ArchiveLimits
{
    public function __construct(
        public int $maxFiles = 1000,
        public int $maxFileSize = 50 * 1024 * 1024,
        public int $maxTotalSize = 250 * 1024 * 1024,
        public int $minFreeSpace = 1024 * 1024 * 1024,
    ) {
    }
}
