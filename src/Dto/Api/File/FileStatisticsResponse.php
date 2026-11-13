<?php

declare(strict_types=1);

namespace App\Dto\Api\File;

final readonly class FileStatisticsResponse
{
    /**
     * @param list<FileStatisticsUser> $users
     * @param array<string, int>       $categories
     */
    public function __construct(
        public int $countFiles,
        public int $sizeFiles,
        public int $countViews,
        public array $users,
        public array $categories,
    ) {
    }
}
