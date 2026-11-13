<?php

declare(strict_types=1);

namespace App\Dto\Api\File;

final readonly class FileStatisticsUser
{
    public function __construct(
        public string $username,
        public int $uploads,
    ) {
    }
}
