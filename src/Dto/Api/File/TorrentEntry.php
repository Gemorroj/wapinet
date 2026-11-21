<?php

declare(strict_types=1);

namespace App\Dto\Api\File;

final readonly class TorrentEntry
{
    public function __construct(
        public string $path,
        public int $size,
    ) {
    }
}
