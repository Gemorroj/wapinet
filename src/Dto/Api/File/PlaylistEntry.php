<?php

declare(strict_types=1);

namespace App\Dto\Api\File;

final readonly class PlaylistEntry
{
    public function __construct(
        public string $title,
        public string $path,
    ) {
    }
}
